<?php

namespace App\Tests\Controller\Advisors;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The Advisors entry in the top menu (base.html.twig), under Program Marketing. Temp users are
 * zz_ictest_-prefixed; tearDown removes them.
 */
class AdvisorMenuTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_ictest_';

    public function testMenuShowsAdvisorsToViewers(): void
    {
        $this->assertSame(true, $this->menuHasAdvisors('menu_view', ['ROLE_ADVISORS_VIEW']));
    }

    public function testMenuShowsAdvisorsToGlobalAdmins(): void
    {
        $this->assertSame(true, $this->menuHasAdvisors('menu_global', ['ROLE_GLOBAL_ADMIN']));
    }

    public function testMenuHidesAdvisorsFromOthers(): void
    {
        $this->assertSame(false, $this->menuHasAdvisors('menu_map', ['ROLE_MAP_VIEW']));
    }

    private function menuHasAdvisors(string $suffix, array $roles): bool
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setUsername(self::USERNAME_PREFIX . $suffix);
        $user->setEmail(self::USERNAME_PREFIX . $suffix . '@test.local');
        $user->setPassword('not-used-by-loginUser');
        $user->setEnabled(1);
        $user->setRoles($roles);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/applinks');
        $this->assertResponseIsSuccessful();

        $links = $crawler->filter('.dropdown-menu a.dropdown-item[href="/advisors"]');
        if ($links->count() === 0) {
            return false;
        }

        // It sits in the Program Marketing menu, beside Degrees & Programs.
        $menuButton = $links->closest('.btn-group')->filter('button.dropdown-toggle');
        $this->assertSame('Program Marketing', trim($menuButton->text()));

        return true;
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
