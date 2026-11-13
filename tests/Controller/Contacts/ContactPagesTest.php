<?php

namespace App\Tests\Controller\Contacts;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Page access for the Contacts app (/contacts...). Temp users are zz_ictest_-prefixed and
 * the test contact is ZZ-prefixed; tearDown removes both.
 */
class ContactPagesTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_ictest_';
    private const NAME_PREFIX = 'ZZ Ic Contact Page Test';

    /**
     * @return array<string, array{0: string[], 1: array<string, int>}> roles => expected status per path
     */
    public static function accessMatrix(): array
    {
        return [
            'view' => [['ROLE_CONTACTS_VIEW'], ['/contacts' => 200, '/contacts/ID' => 200, '/contacts/create' => 403, '/contacts/ID/edit' => 403, '/contacts/manage' => 403]],
            'edit' => [['ROLE_CONTACTS_EDIT'], ['/contacts' => 200, '/contacts/ID' => 200, '/contacts/create' => 200, '/contacts/ID/edit' => 200, '/contacts/manage' => 403]],
            'delete' => [['ROLE_CONTACTS_DELETE'], ['/contacts' => 200, '/contacts/create' => 200, '/contacts/manage' => 403]],
            'admin' => [['ROLE_CONTACTS_ADMIN'], ['/contacts' => 200, '/contacts/create' => 200, '/contacts/ID/edit' => 200, '/contacts/manage' => 200]],
            'global admin alone' => [['ROLE_GLOBAL_ADMIN'], ['/contacts' => 200, '/contacts/ID' => 200, '/contacts/create' => 200, '/contacts/ID/edit' => 200, '/contacts/manage' => 200]],
            'other module' => [['ROLE_MAP_ADMIN'], ['/contacts' => 403, '/contacts/create' => 403, '/contacts/manage' => 403]],
        ];
    }

    /**
     * @dataProvider accessMatrix
     */
    public function testPageAccessMatrix(array $roles, array $expected): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $id = $this->persistContact($em);
        $client->loginUser($this->persistUser($em, 'pages', $roles));

        foreach ($expected as $path => $status) {
            $client->request('GET', str_replace('ID', (string) $id, $path));
            $this->assertSame($status, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testRoutesAreNotCapturedByTheIdRoute(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'ids', ['ROLE_GLOBAL_ADMIN']));

        foreach (['/contacts/create' => 200, '/contacts/manage' => 200, '/contacts/9999999' => 404, '/contacts/9999999/edit' => 404] as $path => $status) {
            $client->request('GET', $path);
            $this->assertSame($status, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testOldAdminPagesAreGone(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'old', ['ROLE_GLOBAL_ADMIN']));

        foreach (['/admin/contacts', '/admin/contacts/create'] as $path) {
            $client->request('GET', $path);
            $this->assertSame(404, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testNavHidesAddAndManageFromViewers(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $client->loginUser($this->persistUser($em, 'nav_view', ['ROLE_CONTACTS_VIEW']));
        $client->request('GET', '/contacts');
        $body = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('href="/contacts/create"', $body);
        $this->assertStringNotContainsString('href="/contacts/manage"', $body);

        $client->loginUser($this->persistUser($em, 'nav_admin', ['ROLE_CONTACTS_ADMIN']));
        $client->request('GET', '/contacts');
        $body = $client->getResponse()->getContent();
        $this->assertStringContainsString('href="/contacts/create"', $body);
        $this->assertStringContainsString('href="/contacts/manage"', $body);
    }

    private function persistContact(EntityManagerInterface $em): int
    {
        $client = static::getClient();
        $client->loginUser($this->persistUser($em, 'seed', ['ROLE_GLOBAL_ADMIN']));
        $client->request('POST', '/api/contacts', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['type' => 'Organization', 'organization' => self::NAME_PREFIX]));
        return json_decode($client->getResponse()->getContent(), true)['id'];
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
            $conn = static::getContainer()->get('doctrine')->getManager()->getConnection();
            $conn->executeStatement('DELETE FROM ic_contacts WHERE organization LIKE ?', [self::NAME_PREFIX . '%']);
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
