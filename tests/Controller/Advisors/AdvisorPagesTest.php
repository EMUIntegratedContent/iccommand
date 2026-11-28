<?php

namespace App\Tests\Controller\Advisors;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Page access for the Advisors app (/advisors...). Temp users are zz_ictest_-prefixed and
 * the test advisor is ZZ-prefixed; each test is rolled back (dama/doctrine-test-bundle).
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

    #[DataProvider('accessMatrix')]
    public function testPageAccessMatrix(array $roles, array $expected): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $id = $this->persistAdvisor($em);
        $client->loginUser(UserFactory::withRoles($roles, self::USERNAME_PREFIX . 'pages'));

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

    #[DataProvider('permissionMatrix')]
    public function testFormGetsPermissionsFromRoles(array $roles, array $expected): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $id = $this->persistAdvisor($em);
        $client->loginUser(UserFactory::withRoles($roles, self::USERNAME_PREFIX . 'perms'));

        $crawler = $client->request('GET', '/advisors/' . $id . '/edit');
        $this->assertResponseIsSuccessful();
        $permissions = json_decode($crawler->filter('advisors-form')->attr(':permissions'), true);
        $this->assertSame([$expected], $permissions);
    }

    public function testRoutesAreNotCapturedByTheIdRoute(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'ids'));

        foreach (['/advisors/create' => 200, '/advisors/manage' => 200, '/advisors/9999999' => 404, '/advisors/9999999/edit' => 404] as $path => $status) {
            $client->request('GET', $path);
            $this->assertSame($status, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testOldAdminPagesAreGone(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'old'));

        foreach (['/admin/advisors', '/admin/advisors/create'] as $path) {
            $client->request('GET', $path);
            $this->assertSame(404, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testNavHidesAddAndManageFromViewers(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_VIEW'], self::USERNAME_PREFIX . 'nav_view'));
        $client->request('GET', '/advisors');
        $body = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('href="/advisors/create"', $body);
        $this->assertStringNotContainsString('href="/advisors/manage"', $body);

        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_ADMIN'], self::USERNAME_PREFIX . 'nav_admin'));
        $client->request('GET', '/advisors');
        $body = $client->getResponse()->getContent();
        $this->assertStringContainsString('href="/advisors/create"', $body);
        $this->assertStringContainsString('href="/advisors/manage"', $body);
    }

    private function persistAdvisor(EntityManagerInterface $em): int
    {
        $client = static::getClient();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'seed'));
        $client->request('POST', '/api/advisors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['type' => 'Organization', 'organization' => self::NAME_PREFIX]));
        return json_decode($client->getResponse()->getContent(), true)['id'];
    }
}
