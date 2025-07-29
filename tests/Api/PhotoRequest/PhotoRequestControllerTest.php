<?php

namespace App\Tests\Api\PhotoRequest;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\PhotoRequest\PhotoRequest;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class PhotoRequestControllerTest extends WebTestCase
{
  private $client = null;

  public function setUp()
  {
    $this->client = static::createClient();
  }

  public function testGetPhotoRequestsList()
  {
    $this->login();

    $crawler = $this->client->request('GET', '/api/photorequests/list');

    // asserts that there is an HTTP response of 200 when requesting the route
    $this->assertEquals(
      200,
      $this->client->getResponse()->getStatusCode()
    );

    // asserts that the "Content-Type" header is "application/json"
    $this->assertTrue(
      $this->client->getResponse()->headers->contains(
        'Content-Type',
        'application/json'
      ),
      $this->client->getResponse()->headers->get('Content-Type')
    );
  }

  public function testSearchPhotoRequests()
  {
    $this->login();

    $crawler = $this->client->request('GET', '/api/photorequests/search?searchterm=test');

    // asserts that there is an HTTP response of 200 when requesting the route
    $this->assertEquals(
      200,
      $this->client->getResponse()->getStatusCode()
    );

    // asserts that the "Content-Type" header is "application/json"
    $this->assertTrue(
      $this->client->getResponse()->headers->contains(
        'Content-Type',
        'application/json'
      ),
      $this->client->getResponse()->headers->get('Content-Type')
    );
  }

  private function logIn()
  {
    $session = $this->client->getContainer()->get('session');

    $firewallName = 'main';
    $firewallContext = 'main';

    $token = new UsernamePasswordToken('cpuzzuol', 'password', $firewallName, array('ROLE_GLOBAL_ADMIN_SUPER'));
    $session->set('_security_' . $firewallContext, serialize($token));
    $session->save();

    $cookie = new Cookie($session->getName(), $session->getId());
    $this->client->getCookieJar()->set($cookie);
  }
}
