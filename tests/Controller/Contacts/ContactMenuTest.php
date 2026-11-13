<?php

namespace App\Tests\Controller\Contacts;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The Contacts entry in the top menu (base.html.twig). Temp users are zz_ictest_-prefixed;
 * tearDown removes them.
 */
class ContactMenuTest extends WebTestCase
{
    private const USERNAME_PREFIX = 'zz_ictest_';

    public function testMenuShowsContactsToViewers(): void
    {
        $this->assertSame(true, $this->menuHasContacts('menu_view', ['ROLE_CONTACTS_VIEW']));
    }

    public function testMenuShowsContactsToGlobalAdmins(): void
    {
        $this->assertSame(true, $this->menuHasContacts('menu_global', ['ROLE_GLOBAL_ADMIN']));
    }

    public function testMenuHidesContactsFromOthers(): void
    {
        $this->assertSame(false, $this->menuHasContacts('menu_map', ['ROLE_MAP_VIEW']));
    }

    private function menuHasContacts(string $suffix, array $roles): bool
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

        return $crawler->filter('.dropdown-menu a.dropdown-item[href="/contacts"]')->count() > 0;
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
