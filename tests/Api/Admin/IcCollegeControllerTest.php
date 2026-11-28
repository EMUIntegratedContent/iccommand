<?php

namespace App\Tests\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Factory\UserFactory;

/**
 * End-to-end CRUD test for the admin college API (/api/admin/colleges), including the
 * rule that a college can't be deleted while a department still uses it. Test rows are
 * ZZ-prefixed and temp users zz_ictest_-prefixed; each test is rolled back (dama/doctrine-test-bundle).
 */
class IcCollegeControllerTest extends WebTestCase
{
    private const NAME_PREFIX = 'ZZ Ic Test';
    private const USERNAME_PREFIX = 'zz_ictest_';

    public function testCrudRoundTrip(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'college_admin'));

        // CREATE
        $this->json($client, 'POST', '/api/admin/colleges', ['college' => self::NAME_PREFIX . ' College', 'url' => '']);
        $this->assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::NAME_PREFIX . ' College', $created['college']);
        $this->assertNull($created['url'], 'Blank URL should be stored as null.');
        $id = $created['id'];

        // VALIDATION: duplicate name, blank name, bad URL -> 422
        $this->json($client, 'POST', '/api/admin/colleges', ['college' => self::NAME_PREFIX . ' College']);
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'POST', '/api/admin/colleges', ['college' => '']);
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'PUT', '/api/admin/colleges/' . $id, ['url' => 'not-a-url']);
        $this->assertResponseStatusCodeSame(422);

        // UPDATE
        $this->json($client, 'PUT', '/api/admin/colleges/' . $id, [
            'college' => self::NAME_PREFIX . ' College Renamed',
            'url' => 'https://www.emich.edu/zz',
        ]);
        $this->assertResponseStatusCodeSame(200);
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::NAME_PREFIX . ' College Renamed', $updated['college']);
        $this->assertSame('https://www.emich.edu/zz', $updated['url']);

        // A department under the college blocks deleting it.
        $this->json($client, 'POST', '/api/admin/departments', ['department' => self::NAME_PREFIX . ' Department', 'collegeId' => $id]);
        $this->assertResponseStatusCodeSame(201);
        $departmentId = json_decode($client->getResponse()->getContent(), true)['id'];

        // LIST + GET one carry the counts
        $client->request('GET', '/api/admin/colleges?page=1&limit=10&searchterm=' . urlencode(self::NAME_PREFIX));
        $this->assertResponseStatusCodeSame(200);
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $list['totalRows']);
        $this->assertSame(1, $list['colleges'][0]['department_count']);
        $this->assertSame(0, $list['colleges'][0]['program_count']);
        $this->assertSame(0, $list['colleges'][0]['scholarship_count']);

        // GET one also lists what uses the college.
        $client->request('GET', '/api/admin/colleges/' . $id);
        $this->assertResponseStatusCodeSame(200);
        $one = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $one['department_count']);
        $this->assertSame([['id' => $departmentId, 'department' => self::NAME_PREFIX . ' Department']], $one['departments']);
        $this->assertSame([], $one['programs']);
        $this->assertSame([], $one['scholarships']);

        $client->request('GET', '/api/admin/colleges/dropdown');
        $this->assertResponseStatusCodeSame(200);
        $this->assertContains($id, array_column(json_decode($client->getResponse()->getContent(), true), 'id'));

        // DELETE blocked while the department exists -> 409 with a reason
        $client->request('DELETE', '/api/admin/colleges/' . $id);
        $this->assertResponseStatusCodeSame(409);
        $conflict = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('in_use', $conflict['error']);
        $this->assertStringContainsString('1 department', $conflict['message']);

        // Remove the department, then the college deletes.
        $client->request('DELETE', '/api/admin/departments/' . $departmentId);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/api/admin/colleges/' . $id);
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/admin/colleges/' . $id);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCollegeInUseByProgramsCannotBeDeleted(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'college_inuse'));

        $collegeId = $em->getConnection()->fetchOne('SELECT college_id FROM program_college_link LIMIT 1');
        if ($collegeId === false) {
            $this->markTestSkipped('No program is linked to a college.');
        }

        $client->request('DELETE', '/api/admin/colleges/' . $collegeId);
        $this->assertResponseStatusCodeSame(409);
        $this->assertStringContainsString('program', json_decode($client->getResponse()->getContent(), true)['message']);

        // GET one lists the linked programs.
        $client->request('GET', '/api/admin/colleges/' . $collegeId);
        $this->assertResponseStatusCodeSame(200);
        $college = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($college['programs']);
        $this->assertSame(['id', 'full_name', 'catalog'], array_keys($college['programs'][0]));
    }

    public function testNonAdminIsForbidden(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_USER'], self::USERNAME_PREFIX . 'plain_user'));

        $client->request('GET', '/api/admin/colleges');
        $this->assertResponseStatusCodeSame(403);
        $client->request('GET', '/admin/colleges');
        $this->assertResponseStatusCodeSame(403);
        $client->request('GET', '/api/admin/departments');
        $this->assertResponseStatusCodeSame(403);
        $client->request('GET', '/admin/departments');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminPagesRender(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'page_admin'));

        foreach (['/admin/colleges', '/admin/colleges/create', '/admin/departments', '/admin/departments/create'] as $url) {
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful($url);
        }
        $client->request('GET', '/admin/colleges/999999/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    private function json(KernelBrowser $client, string $method, string $url, array $data): void
    {
        $client->request($method, $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }
}
