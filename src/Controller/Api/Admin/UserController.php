<?php
namespace App\Controller\Api\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\View\View;


class UserController extends FOSRestController{

  /**
   * Return an individual user (by username)
   */
  public function getUserAction($username){
    $userManager = $this->container->get('fos_user.user_manager');
    $user = $userManager->findUserByUsername($username);

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($user, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Return all defined roles
   */
  public function getRolesAction(){
    $roles = $this->container->getParameter('security.role_hierarchy.roles');

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($roles, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }
}
