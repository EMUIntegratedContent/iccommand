<?php

namespace App\Tests\Map;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\MapItem;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

//FUNCTIONAL AND UNIT TESTING TUTORIAL: https://symfony.com/doc/current/testing.html
class MapItemControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testIndex()
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/map/items');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('map-index')); // make sure Vue component is on the page
    }

    public function testAdd()
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/map/items/create');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame("Create Map Item", $crawler->filter('h1')->text());
        $this->assertCount(1, $crawler->filter('new-map-item')); // make sure Vue component is on the page
    }

    // public function testShow(){
    //     $this->logIn();
    //     $crawler = $this->client->request('GET', '/map/items/{id}');
    //     $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    // }

    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $firewallName = 'main';
        $firewallContext = 'main';

        $token = new UsernamePasswordToken('cpuzzuol', 'password', $firewallName, array('ROLE_GLOBAL_ADMIN_SUPER'));
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
