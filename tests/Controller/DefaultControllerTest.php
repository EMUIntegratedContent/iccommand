<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

//FUNCTIONAL AND UNIT TESTING TUTORIAL: https://symfony.com/doc/current/testing.html
class DefaultControllerTest extends WebTestCase
{
    public function testHomepage()
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/');

        // asserts that there is at least one img tag
        $this->assertGreaterThan(
            0,
            $crawler->filter('img')->count()
        );

        // asserts that there is at least one h2 tag
        // with the class "subtitle"
        $this->assertLessThan(
            1,
            $crawler->filter('h2.subtitle')->count()
        );

        // asserts that there is an HTTP response of 200 when requesting the route
        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode());

       $client->request('GET', '/unittest');

       // asserts that the "Content-Type" header is "application/json"
       $this->assertTrue(
           $client->getResponse()->headers->contains(
               'Content-Type',
               'application/json; charset=UTF-8'
           ),
           $client->getResponse()->headers->get('Content-Type') // optional message shown on failure
       );
    }
}
