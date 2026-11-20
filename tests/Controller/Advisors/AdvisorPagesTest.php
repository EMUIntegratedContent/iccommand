<?php

namespace App\Tests\Controller\Advisors;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Page access for the Advisors app (/advisors...). Temp users are zz_ictest_-prefixed and
 * the test advisor is ZZ-prefixed; tearDown removes both.
 */
class AdvisorPagesTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_ictest_';
    private const NAME_PREFIX = 'ZZ Ic Advisor Page Test';

    /**
     * @return array<string, array{0: string[], 1: array<string, int>}> roles => expected status per path
     */
    public static function accessMatrix(): array
    {
        return [
            'view' => [['ROLE_ADVISORS_VIEW'], ['/advisors' => 200, '/advisors/ID' => 200, '/advisors/create' => 403, '/advisors/ID/edit' => 403, '/advisors/manage' => 403]],
            'edit' => [['ROLE_ADVISORS_EDIT'], ['/advisors' => 200, '/advisors/ID' => 200, '/advisors/create' => 200, '/advisors/ID/edit' => 200, '/advisors/manage' => 403]],
            'delete' => [['ROLE_ADVISORS_DELETE'], ['/advisors' => 200, '/advisors/create' => 200, '/advisors/manage' => 403]],
            'admin' => [['ROLE_ADVISORS_ADMIN'], ['/advisors' => 200, '/advisors/create' => 200, '/advisors/ID/edit' => 200, '/advisors/manage' => 200]],
            'global admin alone' => [['ROLE_GLOBAL_ADMIN'], ['/advisors' => 200, '/advisors/ID' => 200, '/advisors/create' => 200, '/advisors/ID/edit' => 200, '/advisors/manage' => 200]],
            'other module' => [['ROLE_MAP_ADMIN'], ['/advisors' => 403, '/advisors/create' => 403, '/advisors/manage' => 403]],
        ];
    }

    /**
     * @dataProvider accessMatrix
     */
    public function testPageAccessMatrix(array $roles, array $expected): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $id = $this->persistAdvisor($em);
        $client->loginUser($this->persistUser($em, 'pages', $roles));

        foreach ($expected as $path => $status) {
            $client->request('GET', str_replace('ID', (string) $id, $path));
            $this->assertSame($status, $client->getResponse()->getStatusCode(), $path);
        }
    }

    /**
     * @return array<string, array{0: string[], 1: array}> roles => the permissions the form gets
     */
    public static function permissionMatrix(): array
    {
        $none = ['programLinks' => null, 'scholarshipLinks' => null];

        return [
            'edit only' => [['ROLE_ADVISORS_EDIT'], ['admin' => false, 'delete' => false, 'edit' => true, 'view' => true] + $none],
            'admin' => [['ROLE_ADVISORS_ADMIN'], ['admin' => true, 'delete' => true, 'edit' => true, 'view' => true] + $none],
            // Usage list links go to the page the user's program/scholarship role can open.
            'edit, program viewer, scholarship editor' => [
                ['ROLE_ADVISORS_EDIT', 'ROLE_PROGRAMS_VIEW', 'ROLE_SCHOLARSHIP_EDIT'],
                ['admin' => false, 'delete' => false, 'edit' => true, 'view' => true, 'programLinks' => 'show', 'scholarshipLinks' => 'edit'],
            ],
            'global admin alone' => [['ROLE_GLOBAL_ADMIN'], ['admin' => true, 'delete' => true, 'edit' => true, 'view' => true, 'programLinks' => 'edit', 'scholarshipLinks' => 'edit']],
        ];
    }

    /**
     * @dataProvider permissionMatrix
     */
    public function testFormGetsPermissionsFromRoles(array $roles, array $expected): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $id = $this->persistAdvisor($em);
        $client->loginUser($this->persistUser($em, 'perms', $roles));

        $crawler = $client->request('GET', '/advisors/' . $id . '/edit');
        $this->assertResponseIsSuccessful();
        $permissions = json_decode($crawler->filter('advisors-form')->attr(':permissions'), true);
        $this->assertSame([$expected], $permissions);
    }

    public function testRoutesAreNotCapturedByTheIdRoute(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'ids', ['ROLE_GLOBAL_ADMIN']));

        foreach (['/advisors/create' => 200, '/advisors/manage' => 200, '/advisors/9999999' => 404, '/advisors/9999999/edit' => 404] as $path => $status) {
            $client->request('GET', $path);
            $this->assertSame($status, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testOldAdminPagesAreGone(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'old', ['ROLE_GLOBAL_ADMIN']));

        foreach (['/admin/advisors', '/admin/advisors/create'] as $path) {
            $client->request('GET', $path);
            $this->assertSame(404, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testNavHidesAddAndManageFromViewers(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $client->loginUser($this->persistUser($em, 'nav_view', ['ROLE_ADVISORS_VIEW']));
        $client->request('GET', '/advisors');
        $body = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('href="/advisors/create"', $body);
        $this->assertStringNotContainsString('href="/advisors/manage"', $body);

        $client->loginUser($this->persistUser($em, 'nav_admin', ['ROLE_ADVISORS_ADMIN']));
        $client->request('GET', '/advisors');
        $body = $client->getResponse()->getContent();
        $this->assertStringContainsString('href="/advisors/create"', $body);
        $this->assertStringContainsString('href="/advisors/manage"', $body);
    }

    private function persistAdvisor(EntityManagerInterface $em): int
    {
        $client = static::getClient();
        $client->loginUser($this->persistUser($em, 'seed', ['ROLE_GLOBAL_ADMIN']));
        $client->request('POST', '/api/advisors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['type' => 'Organization', 'organization' => self::NAME_PREFIX]));
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
            $conn->executeStatement('DELETE FROM ic_advisors WHERE organization LIKE ?', [self::NAME_PREFIX . '%']);
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
