<?php

namespace App\Tests\Api\Contacts;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Role wiring for the Contacts app: the ROLE_CONTACTS_* hierarchy and access management
 * (roles list, per-app user lists). Temp users are zz_ictest_-prefixed; tearDown removes them.
 */
class ContactAccessTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_ictest_';
    private const ROLES = ['ROLE_CONTACTS_VIEW', 'ROLE_CONTACTS_EDIT', 'ROLE_CONTACTS_DELETE', 'ROLE_CONTACTS_ADMIN'];

    public function testRoleHierarchy(): void
    {
        static::bootKernel();
        $hierarchy = static::getContainer()->get(RoleHierarchyInterface::class);

        $admin = $hierarchy->getReachableRoleNames(['ROLE_CONTACTS_ADMIN']);
        foreach (['ROLE_CONTACTS_DELETE', 'ROLE_CONTACTS_EDIT', 'ROLE_CONTACTS_VIEW', 'ROLE_USER'] as $role) {
            $this->assertContains($role, $admin);
        }

        $view = $hierarchy->getReachableRoleNames(['ROLE_CONTACTS_VIEW']);
        $this->assertNotContains('ROLE_CONTACTS_EDIT', $view);
        $this->assertContains('ROLE_USER', $view);
    }

    public function testRolesEndpointListsContactsRoles(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em, 'roles_admin', ['ROLE_CONTACTS_ADMIN']));

        $client->request('GET', '/api/admin/roles');

        $this->assertResponseIsSuccessful();
        $roles = array_keys(json_decode($client->getResponse()->getContent(), true));
        foreach (self::ROLES as $role) {
            $this->assertContains($role, $roles);
        }
    }

    public function testAdminManagesOnlyContactsUsers(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $client->loginUser($this->persistUser($em, 'app_admin', ['ROLE_CONTACTS_ADMIN']));
        $client->request('GET', '/api/admin/appusers/ROLE_CONTACTS_');
        $this->assertResponseStatusCodeSame(200);
        $client->request('GET', '/api/admin/appusers/ROLE_MAP_');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser($this->persistUser($em, 'app_viewer', ['ROLE_CONTACTS_VIEW']));
        $client->request('GET', '/api/admin/appusers/ROLE_CONTACTS_');
        $this->assertResponseStatusCodeSame(403);
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
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
