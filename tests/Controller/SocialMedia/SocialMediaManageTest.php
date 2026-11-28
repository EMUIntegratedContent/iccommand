<?php

namespace App\Tests\Controller\SocialMedia;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Factory\UserFactory;

/**
 * Verifies the Social Media Directory management page + the admin user API accept an
 * app admin (ROLE_SOCIAL_ADMIN), and that a plain app user (ROLE_SOCIAL_USER) is
 * denied the management page. Temp users are persisted (then cleaned up) so the
 * security provider can refresh them by identifier across requests.
 */
class SocialMediaManageTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_smtest_';

    public function testManagePageAccessibleToAppAdmin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $admin = UserFactory::withRoles(['ROLE_SOCIAL_ADMIN'], self::USERNAME_PREFIX . 'admin_page');
        $client->loginUser($admin);

        $client->request('GET', '/social-media/manage');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Social Media Directory App Management');
    }

    public function testManagePageForbiddenToPlainAppUser(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = UserFactory::withRoles(['ROLE_SOCIAL_USER'], self::USERNAME_PREFIX . 'plain_user');
        $client->loginUser($user);

        $client->request('GET', '/social-media/manage');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminUserApiAcceptsAppAdmin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $admin = UserFactory::withRoles(['ROLE_SOCIAL_ADMIN'], self::USERNAME_PREFIX . 'admin_api');
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
        $admin = UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'global_admin');
        $target = UserFactory::withRoles(['ROLE_USER'], self::USERNAME_PREFIX . 'role_target');
        $client->loginUser($admin);

        // Simulates checking the "Social Media Directory" role box + saving on the admin user page.
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
}
