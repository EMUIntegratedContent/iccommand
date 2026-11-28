<?php

namespace App\Tests\Api\Advisors;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use App\Tests\Factory\UserFactory;

/**
 * Role wiring for the Advisors app: the ROLE_ADVISORS_* hierarchy and access management
 * (roles list, per-app user lists). Temp users are zz_ictest_-prefixed; each test is rolled back (dama/doctrine-test-bundle).
 */
class AdvisorAccessTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_ictest_';
    private const ROLES = ['ROLE_ADVISORS_VIEW', 'ROLE_ADVISORS_EDIT', 'ROLE_ADVISORS_DELETE', 'ROLE_ADVISORS_ADMIN'];

    public function testRoleHierarchy(): void
    {
        static::bootKernel();
        $hierarchy = static::getContainer()->get(RoleHierarchyInterface::class);

        $admin = $hierarchy->getReachableRoleNames(['ROLE_ADVISORS_ADMIN']);
        foreach (['ROLE_ADVISORS_DELETE', 'ROLE_ADVISORS_EDIT', 'ROLE_ADVISORS_VIEW', 'ROLE_USER'] as $role) {
            $this->assertContains($role, $admin);
        }

        $view = $hierarchy->getReachableRoleNames(['ROLE_ADVISORS_VIEW']);
        $this->assertNotContains('ROLE_ADVISORS_EDIT', $view);
        $this->assertContains('ROLE_USER', $view);
    }

    public function testRolesEndpointListsAdvisorsRoles(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_ADMIN'], self::USERNAME_PREFIX . 'roles_admin'));

        $client->request('GET', '/api/admin/roles');

        $this->assertResponseIsSuccessful();
        $roles = array_keys(json_decode($client->getResponse()->getContent(), true));
        foreach (self::ROLES as $role) {
            $this->assertContains($role, $roles);
        }
    }

    public function testAdminManagesOnlyAdvisorsUsers(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_ADMIN'], self::USERNAME_PREFIX . 'app_admin'));
        $client->request('GET', '/api/admin/appusers/ROLE_ADVISORS_');
        $this->assertResponseStatusCodeSame(200);
        $client->request('GET', '/api/admin/appusers/ROLE_MAP_');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_VIEW'], self::USERNAME_PREFIX . 'app_viewer'));
        $client->request('GET', '/api/admin/appusers/ROLE_ADVISORS_');
        $this->assertResponseStatusCodeSame(403);
    }
}
