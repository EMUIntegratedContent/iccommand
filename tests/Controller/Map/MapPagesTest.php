<?php

namespace App\Tests\Controller\Map;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The Map app's Twig pages: access_control lets in ROLE_MAP_VIEW and global admins, and
 * /map/manage additionally needs ROLE_MAP_ADMIN. Each page mounts its Vue component.
 */
class MapPagesTest extends WebTestCase
{
    public function testIndexMountsTheMapApp(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_VIEW']));

        $crawler = $client->request('GET', '/map');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('map-index'));
    }

    public function testCreatePageMountsTheForm(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN']));

        $crawler = $client->request('GET', '/map/items/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('h1', 'Create Map Item');
        $this->assertCount(1, $crawler->filter('new-map-item'));
    }

    public function testManagePageNeedsMapAdmin(): void
    {
        $client = static::createClient();

        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_VIEW']));
        $client->request('GET', '/map/manage');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_ADMIN']));
        $client->request('GET', '/map/manage');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('h1', 'Map app management');
    }

    public function testAnonymousIsSentToTheLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/map');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }
}
