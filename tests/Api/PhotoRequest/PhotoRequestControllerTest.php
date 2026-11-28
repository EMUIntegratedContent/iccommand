<?php

namespace App\Tests\Api\PhotoRequest;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The Photo Request admin list and search return JSON to photo viewers.
 */
class PhotoRequestControllerTest extends WebTestCase
{
    public function testListReturnsJson(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::withRoles(['ROLE_PHOTO_VIEW']));

        $client->request('GET', '/api/photorequests/list');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testSearchReturnsJson(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::withRoles(['ROLE_PHOTO_VIEW']));

        $client->request('GET', '/api/photorequests/search?searchterm=test');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($client->getResponse()->getContent());
    }
}
