<?php

namespace App\Tests\Controller\Map;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Doctrine\DBAL\Schema\Schema;

//FUNCTIONAL AND UNIT TESTING TUTORIAL: https://symfony.com/doc/current/testing.html
class MapControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testMap()
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/map');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Campus Map Application', $crawler->filter('h1')->text());
    }

    public function testManage()
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/map/manage');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Map app management', $crawler->filter('h1')->text());
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
        $token = new UsernamePasswordToken('user', null, $firewallName, array('ROLE_GLOBAL_ADMIN_SUPER'));
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
