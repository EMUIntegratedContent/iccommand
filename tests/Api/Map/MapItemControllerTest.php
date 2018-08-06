<?php

namespace App\Tests\Api\Map;

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

    public function testGetItem()
    {
        $this->login();

        $crawler = $this->client->request('GET', '/map/items/1');

        // asserts that there is an HTTP response of 200 when requesting the route
        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode());

       //$client->request('GET', '/map');

       // asserts that the "Content-Type" header is "text/html"
       $this->assertTrue(
           $this->client->getResponse()->headers->contains(
               'Content-Type',
               'application/json'
           ),
           $this->client->getResponse()->headers->get('Content-Type') // optional message shown on failure
       );
    }

    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $firewallName = 'main';
        // if you don't define multiple connected firewalls, the context defaults to the firewall name
        // See https://symfony.com/doc/current/reference/configuration/security.html#firewall-context
        $firewallContext = 'main';

        // you may need to use a different token class depending on your application.
        // for example, when using Guard authentication you must instantiate PostAuthenticationGuardToken
        $token = new UsernamePasswordToken('cpuzzuol', 'password', $firewallName, array('ROLE_GLOBAL_ADMIN_SUPER'));
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
