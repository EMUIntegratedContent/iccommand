<?php

namespace App\Tests\Controller;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testHomepageNeedsALogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testHomepageLoadsForAnyUser(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::withRoles([]));

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }
}
