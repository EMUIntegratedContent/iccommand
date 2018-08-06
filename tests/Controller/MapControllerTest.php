<?php

namespace App\Tests\Controller\Map;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

//FUNCTIONAL AND UNIT TESTING TUTORIAL: https://symfony.com/doc/current/testing.html
class MapControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/map');

        // asserts that there is an HTTP response of 200 when requesting the route
        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode());

       $client->request('GET', '/map');

       // asserts that the "Content-Type" header is "text/html"
       $this->assertTrue(
           $client->getResponse()->headers->contains(
               'Content-Type',
               'text/html; charset=UTF-8'
           ),
           $client->getResponse()->headers->get('Content-Type') // optional message shown on failure
       );
    }

    public function testManage(){
        $client = static::createClient();
        $crawler = $client->request('GET','/map/manage');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode());

        $client->request('GET', '/map');
    }
}
