<?php

namespace App\Tests\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * End-to-end test for the admin contact API (/api/admin/contacts), the rule that a contact
 * can't be deleted while a program, scholarship or building uses it, and the picker's options route. Test rows are
 * ZZ-prefixed and temp users zz_ictest_-prefixed; tearDown removes both.
 */
class IcContactControllerTest extends WebTestCase
{
    private const NAME_PREFIX = 'ZZ Ic Contact Test';
    private const USERNAME_PREFIX = 'zz_ictest_';

    public function testCrudRoundTrip(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'contact_admin', ['ROLE_GLOBAL_ADMIN']));

        // CREATE
        $this->json($client, 'POST', '/api/admin/contacts', [
            'type' => 'Person',
            'lastName' => self::NAME_PREFIX . ' Doe',
            'firstName' => 'Jane',
            'email' => 'zz.jane@emich.edu',
            'phone' => '734-487-1234',
            'room' => '101',
            'area' => 'Admissions',
            'url' => 'https://www.emich.edu/jane',
            'foragerUrl' => 'https://www.emich.edu/forager',
            'imageUrl' => 'https://www.emich.edu/jane.jpg',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Person', $created['type']);
        $this->assertSame(self::NAME_PREFIX . ' Doe, Jane', $created['displayName']);
        $this->assertSame('https://www.emich.edu/forager', $created['foragerUrl']);
        // Audit fields hold the username, like the other Blameable entities.
        $this->assertSame(self::USERNAME_PREFIX . 'contact_admin', $created['createdBy']);
        $this->assertSame(self::USERNAME_PREFIX . 'contact_admin', $created['updatedBy']);
        $this->assertNotEmpty($created['created']);
        $id = $created['id'];

        // VALIDATION -> 422
        $invalid = [
            'unknown type' => ['type' => 'Robot', 'lastName' => 'Doe', 'firstName' => 'Jane'],
            'missing type' => ['lastName' => 'Doe', 'firstName' => 'Jane'],
            'person without last name' => ['type' => 'Person', 'firstName' => 'Jane'],
            'person without first name' => ['type' => 'Person', 'lastName' => 'Doe'],
            'organization without a name' => ['type' => 'Organization', 'lastName' => 'Doe', 'firstName' => 'Jane'],
            'bad email' => ['type' => 'Organization', 'organization' => 'ZZ', 'email' => 'nope'],
            'bad url' => ['type' => 'Organization', 'organization' => 'ZZ', 'url' => 'nope'],
            'script url' => ['type' => 'Organization', 'organization' => 'ZZ', 'url' => 'javascript:alert(1)'],
            'bad forager url' => ['type' => 'Organization', 'organization' => 'ZZ', 'foragerUrl' => 'nope'],
            'bad image url' => ['type' => 'Organization', 'organization' => 'ZZ', 'imageUrl' => 'nope'],
        ];
        foreach ($invalid as $label => $payload) {
            $this->json($client, 'POST', '/api/admin/contacts', $payload);
            $this->assertSame(422, $client->getResponse()->getStatusCode(), $label);
        }

        // UPDATE: a person can become an organization
        $this->json($client, 'PUT', '/api/admin/contacts/' . $id, [
            'type' => 'Organization',
            'organization' => self::NAME_PREFIX . ' Office',
            'lastName' => null,
            'firstName' => null,
            'email' => '',
        ]);
        $this->assertResponseStatusCodeSame(200);
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::NAME_PREFIX . ' Office', $updated['displayName']);
        $this->assertNull($updated['email']);
        $this->assertSame('734-487-1234', $updated['phone']); // Omitted keys are left alone.

        // LIST + GET one carry the display name, counts and usage lists
        $client->request('GET', '/api/admin/contacts?page=1&limit=10&searchterm=' . urlencode(self::NAME_PREFIX . ' Office'));
        $this->assertResponseStatusCodeSame(200);
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $list['totalRows']);
        $this->assertSame(self::NAME_PREFIX . ' Office', $list['contacts'][0]['display_name']);
        foreach (['program', 'scholarship', 'building'] as $noun) {
            $this->assertSame(0, $list['contacts'][0][$noun . '_count']);
        }

        $client->request('GET', '/api/admin/contacts/' . $id);
        $this->assertResponseStatusCodeSame(200);
        $one = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Organization', $one['type']);
        $this->assertSame('https://www.emich.edu/forager', $one['forager_url']);
        $this->assertSame(self::USERNAME_PREFIX . 'contact_admin', $one['updated_by']);
        foreach (['programs', 'scholarships', 'buildings'] as $key) {
            $this->assertSame([], $one[$key]);
        }

        // DELETE (unused) -> 204, then 404
        $client->request('DELETE', '/api/admin/contacts/' . $id);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', '/api/admin/contacts/' . $id);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testContactInUseCannotBeDeleted(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'contact_inuse', ['ROLE_GLOBAL_ADMIN']));

        $contactId = $this->createOrganization($client, self::NAME_PREFIX . ' In Use');
        $otherId = $this->createOrganization($client, self::NAME_PREFIX . ' Other');

        // A scholarship can have several contacts.
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['contactIds' => [$contactId, $otherId]]));
        $this->assertResponseStatusCodeSame(201);
        $scholarship = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$contactId, $otherId], $scholarship['contactIds']);
        $this->assertEqualsCanonicalizing([self::NAME_PREFIX . ' In Use', self::NAME_PREFIX . ' Other'], $scholarship['contactNames']);

        $client->request('DELETE', '/api/admin/contacts/' . $contactId);
        $this->assertResponseStatusCodeSame(409);
        $conflict = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('in_use', $conflict['error']);
        $this->assertStringContainsString('1 scholarship', $conflict['message']);

        $client->request('GET', '/api/admin/contacts/' . $contactId);
        $one = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $one['scholarship_count']);
        $this->assertSame(self::NAME_PREFIX . ' Scholarship', $one['scholarships'][0]['title']);

        // Removing one contact frees only that contact.
        $this->json($client, 'PUT', '/api/scholarships/' . $scholarship['id'], ['contactIds' => [$otherId]]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$otherId], json_decode($client->getResponse()->getContent(), true)['contactIds']);
        $client->request('DELETE', '/api/admin/contacts/' . $contactId);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/api/admin/contacts/' . $otherId);
        $this->assertResponseStatusCodeSame(409);

        // Deleting the scholarship drops its links, which frees the other contact.
        $client->request('DELETE', '/api/scholarships/' . $scholarship['id']);
        $this->assertSame(204, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $client->request('DELETE', '/api/admin/contacts/' . $otherId);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testUnknownContactIsRejected(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'contact_unknown', ['ROLE_GLOBAL_ADMIN']));

        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['contactIds' => [999999999]]));
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['contactIds' => ['abc']]));
        $this->assertResponseStatusCodeSame(422);

        // The same payload without the bad id saves, so the 422s above were the contact.
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['contactIds' => []]));
        $this->assertResponseStatusCodeSame(201);
    }

    public function testOptionsRouteIsOpenToEditorsOnly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'contact_seed', ['ROLE_GLOBAL_ADMIN']));
        $contactId = $this->createOrganization($client, self::NAME_PREFIX . ' Option');

        // Module editors can read the options, but not the admin API.
        foreach (['ROLE_MAP_CREATE', 'ROLE_PROGRAMS_CREATE', 'ROLE_SCHOLARSHIP_CREATE', 'ROLE_SCHOLARSHIP_ADMIN'] as $index => $role) {
            $client->loginUser($this->persistUser($em, 'contact_editor' . $index, [$role]));

            $client->request('GET', '/api/ic/contacts/options');
            $this->assertSame(200, $client->getResponse()->getStatusCode(), $role);
            $options = json_decode($client->getResponse()->getContent(), true);
            $option = current(array_filter($options, fn (array $row) => $row['id'] === $contactId));
            $this->assertSame(['id', 'displayName', 'type', 'email'], array_keys($option));
            $this->assertSame(self::NAME_PREFIX . ' Option', $option['displayName']);

            $client->request('GET', '/api/admin/contacts');
            $this->assertSame(403, $client->getResponse()->getStatusCode(), $role);
        }

        // View-only users don't get the picker, so they don't get its data.
        $client->loginUser($this->persistUser($em, 'contact_viewer', ['ROLE_MAP_VIEW']));
        $client->request('GET', '/api/ic/contacts/options');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOptionsRouteNeedsALogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/ic/contacts/options');
        $this->assertResponseStatusCodeSame(401);
    }

    private function scholarshipPayload(array $extra): array
    {
        return $extra + [
            'title' => self::NAME_PREFIX . ' Scholarship',
            'overview' => '<p>Overview</p>',
            'url' => 'https://www.emich.edu/zz',
            'description' => '<p>Description</p>',
            'contact' => '<p>Free-text contact</p>',
            'active' => true,
        ];
    }

    private function createOrganization(KernelBrowser $client, string $name): int
    {
        $this->json($client, 'POST', '/api/admin/contacts', ['type' => 'Organization', 'organization' => $name]);
        $this->assertResponseStatusCodeSame(201);
        return json_decode($client->getResponse()->getContent(), true)['id'];
    }

    private function json(KernelBrowser $client, string $method, string $url, array $data): void
    {
        $client->request($method, $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    private function persistUser(EntityManagerInterface $em, string $suffix, array $roles): User
    {
        $user = new User();
        $user->setUsername(self::USERNAME_PREFIX . $suffix);
        $user->setEmail(self::USERNAME_PREFIX . $suffix . '@test.local');
        $user->setPassword('not-used-by-loginUser');
        $user->setEnabled(1);
        $user->setRoles($roles);
        $em->persist($user);
        $em->flush();
        return $user;
    }

    protected function tearDown(): void
    {
        if (static::$booted) {
            $em = static::getContainer()->get('doctrine')->getManager();
            $conn = $em->getConnection();
            $conn->executeStatement('DELETE FROM scholarships_scholarship WHERE schlrshp_title LIKE ?', [self::NAME_PREFIX . '%']);
            $conn->executeStatement('DELETE FROM ic_contacts WHERE organization LIKE ? OR last_name LIKE ?', [self::NAME_PREFIX . '%', self::NAME_PREFIX . '%']);
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
