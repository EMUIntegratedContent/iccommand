<?php

namespace App\Tests\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * End-to-end CRUD test for the admin department API (/api/admin/departments), including
 * moving a department to another college and the rule that a department can't be deleted
 * while programs or scholarships use it. Test rows are ZZ-prefixed and temp users
 * zz_ictest_-prefixed; tearDown removes both.
 */
class IcDepartmentControllerTest extends WebTestCase
{
    private const NAME_PREFIX = 'ZZ Ic Dept Test';
    private const USERNAME_PREFIX = 'zz_ictest_';

    public function testCrudRoundTrip(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'dept_admin', ['ROLE_GLOBAL_ADMIN']));

        $collegeA = $this->createCollege($client, self::NAME_PREFIX . ' College A');
        $collegeB = $this->createCollege($client, self::NAME_PREFIX . ' College B');

        // CREATE
        $this->json($client, 'POST', '/api/admin/departments', ['department' => self::NAME_PREFIX . ' Department', 'collegeId' => $collegeA]);
        $this->assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::NAME_PREFIX . ' Department', $created['department']);
        $this->assertSame($collegeA, $created['collegeId']);
        $this->assertSame(self::NAME_PREFIX . ' College A', $created['collegeName']);
        $id = $created['id'];

        // VALIDATION: duplicate name, blank name, missing or unknown college -> 422
        $this->json($client, 'POST', '/api/admin/departments', ['department' => self::NAME_PREFIX . ' Department', 'collegeId' => $collegeA]);
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'POST', '/api/admin/departments', ['department' => '', 'collegeId' => $collegeA]);
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'POST', '/api/admin/departments', ['department' => self::NAME_PREFIX . ' No College', 'collegeId' => '']);
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'PUT', '/api/admin/departments/' . $id, ['collegeId' => 999999]);
        $this->assertResponseStatusCodeSame(422);
        $violations = json_decode($client->getResponse()->getContent(), true)['violations'];
        $this->assertSame('college', $violations[0]['propertyPath']);

        // UPDATE: rename and move to another college
        $this->json($client, 'PUT', '/api/admin/departments/' . $id, [
            'department' => self::NAME_PREFIX . ' Department Renamed',
            'collegeId' => $collegeB,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::NAME_PREFIX . ' Department Renamed', $updated['department']);
        $this->assertSame($collegeB, $updated['collegeId']);

        // LIST + GET one carry the college name and counts
        $client->request('GET', '/api/admin/departments?page=1&limit=10&searchterm=' . urlencode(self::NAME_PREFIX . ' Department'));
        $this->assertResponseStatusCodeSame(200);
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $list['totalRows']);
        $this->assertSame(self::NAME_PREFIX . ' College B', $list['departments'][0]['college']);
        $this->assertSame(0, $list['departments'][0]['program_count']);
        $this->assertSame(0, $list['departments'][0]['scholarship_count']);

        $client->request('GET', '/api/admin/departments/' . $id);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame($collegeB, json_decode($client->getResponse()->getContent(), true)['college_id']);

        // DELETE (unused) -> 204, then 404
        $client->request('DELETE', '/api/admin/departments/' . $id);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', '/api/admin/departments/' . $id);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDepartmentInUseCannotBeDeleted(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'dept_inuse', ['ROLE_GLOBAL_ADMIN']));

        $departmentId = $em->getConnection()->fetchOne('SELECT department_id FROM program_inter_dept LIMIT 1');
        if ($departmentId === false) {
            $this->markTestSkipped('No program is linked to a department.');
        }

        $client->request('DELETE', '/api/admin/departments/' . $departmentId);
        $this->assertResponseStatusCodeSame(409);
        $conflict = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('in_use', $conflict['error']);
        $this->assertStringContainsString('program', $conflict['message']);

        // Still there.
        $client->request('GET', '/api/admin/departments/' . $departmentId);
        $this->assertResponseStatusCodeSame(200);
    }

    private function createCollege(KernelBrowser $client, string $name): int
    {
        $this->json($client, 'POST', '/api/admin/colleges', ['college' => $name]);
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
            $conn->executeStatement('DELETE FROM ic_departments WHERE department LIKE ?', [self::NAME_PREFIX . '%']);
            $conn->executeStatement('DELETE FROM ic_colleges WHERE college LIKE ?', [self::NAME_PREFIX . '%']);
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
