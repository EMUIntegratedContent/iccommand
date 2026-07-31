<?php

namespace App\Tests\Controller\SocialMedia;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Verifies the Social Media Links management page + the admin user API accept an
 * app admin (ROLE_SOCIAL_ADMIN), and that a plain app user (ROLE_SOCIAL_USER) is
 * denied the management page. Temp users are persisted (then cleaned up) so the
 * security provider can refresh them by identifier across requests.
 */
class SocialMediaManageTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_smtest_';

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

    public function testManagePageAccessibleToAppAdmin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $admin = $this->persistUser($em, 'admin_page', ['ROLE_SOCIAL_ADMIN']);
        $client->loginUser($admin);

        $client->request('GET', '/social-media/manage');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Social Media Links App Management');
    }

    public function testManagePageForbiddenToPlainAppUser(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $this->persistUser($em, 'plain_user', ['ROLE_SOCIAL_USER']);
        $client->loginUser($user);

        $client->request('GET', '/social-media/manage');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminUserApiAcceptsAppAdmin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $admin = $this->persistUser($em, 'admin_api', ['ROLE_SOCIAL_ADMIN']);
        $client->loginUser($admin);

        // Roles endpoint must accept the social app admin and expose the social roles.
        $client->request('GET', '/api/admin/roles');
        $this->assertResponseIsSuccessful();
        $roles = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('ROLE_SOCIAL_USER', $roles);
        $this->assertArrayHasKey('ROLE_SOCIAL_ADMIN', $roles);

        // App-users endpoints (used by the manage UI) must accept the social app admin.
        $client->request('GET', '/api/admin/appusers/ROLE_SOCIAL_');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/admin/appusers/not/ROLE_SOCIAL_');
        $this->assertResponseIsSuccessful();
    }

    public function testGlobalAdminCanAssignSocialRoleViaUserApi(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $admin = $this->persistUser($em, 'global_admin', ['ROLE_GLOBAL_ADMIN']);
        $target = $this->persistUser($em, 'role_target', ['ROLE_USER']);
        $client->loginUser($admin);

        // Simulates checking the "Social Media Links" role box + saving on the admin user page.
        $client->request(
            'PUT',
            '/api/admin/users/' . $target->getUsername(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'firstName' => 'Role',
                'lastName' => 'Target',
                'jobTitle' => '',
                'department' => '',
                'phone' => '',
                'roles' => ['ROLE_USER', 'ROLE_SOCIAL_ADMIN'],
                'enabled' => 1,
            ])
        );
        $this->assertResponseIsSuccessful();

        // The social role must persist on the user.
        $em->clear();
        $reloaded = $em->getRepository(User::class)->find($target->getId());
        $this->assertContains('ROLE_SOCIAL_ADMIN', $reloaded->getRoles());
    }

    protected function tearDown(): void
    {
        if (static::$booted) {
            $em = static::getContainer()->get('doctrine')->getManager();
            $leftovers = $em->getRepository(User::class)->createQueryBuilder('u')
                ->where('u.username LIKE :prefix')
                ->setParameter('prefix', self::USERNAME_PREFIX . '%')
                ->getQuery()
                ->getResult();
            foreach ($leftovers as $row) {
                $em->remove($row);
            }
            if ($leftovers) {
                $em->flush();
            }
        }
        parent::tearDown();
    }
}
