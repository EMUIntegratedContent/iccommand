<?php
namespace App\Controller\Api\Admin;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use App\Service\UserService;


class UserController extends AbstractFOSRestController{

  private $service;

  public function __construct(UserService $service){
    $this->service = $service;
  }

  /**
   * Get all users
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN')")
   */
  public function getUsersAction() : Response
  {
    $users = $this->getDoctrine()->getRepository(User::class)->findBy([],['username' => 'asc']);

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($users, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Return an individual user (by username)
   *
   * @Security("has_role('ROLE_USER')")
   */
  public function getUserAction($username) : Response
  {
    $userManager = $this->container->get('fos_user.user_manager');
    $user = $userManager->findUserByUsername($username);

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($user, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Return all defined roles
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_ADMIN')")
   */
  public function getRolesAction() : Response
  {
    $roles = $this->container->getParameter('security.role_hierarchy.roles');

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($roles, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Update a user's information and roles
   *
   * @Security("has_role('ROLE_USER')")
   */
   public function putUserAction(Request $request, $username) : Response
   {

     $userManager = $this->container->get('fos_user.user_manager');
     $user = $userManager->findUserByUsername($username);

     if(!$user){
       throw $this->createNotFoundException('The user ' . $username . ' was not found.');
     }

     $user->setFirstName($request->request->get('firstName'));
     $user->setLastName($request->request->get('lastName'));
     $user->setJobTitle($request->request->get('jobTitle'));
     $user->setDepartment($request->request->get('department'));
     $user->setPhone($request->request->get('phone'));

     $updatedRoles = $request->request->get('roles');
     $this->service->syncUserRoles($user, $updatedRoles);
     $this->service->setUserEnabledStatus($user, $request->request->get('enabled'));

     $userManager->updateUser($user);

     $response = new Response('User ' . $username . ' was updated successfully.', 200, array('Content-Type' => 'application/json'));
     return $response;
   }

   /**
    * Return all users of an application
    *
    * @param String $rolePrefix (e.g. the role prefix for map app users is 'ROLE_MAP_')
    * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_ADMIN')")
    */
   public function getAppusersAction($rolePrefix) : Response
   {
     $mapAppUsers = $this->getDoctrine()->getRepository(User::class)->findByRole($rolePrefix);

     $serializer = $this->container->get('jms_serializer');
     $serialized = $serializer->serialize($mapAppUsers, 'json');
     $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

     return $response;
   }

   /**
    * Return all users that are NOT part of an application
    *
    * @param String $rolePrefix (e.g. the role prefix for map app users is 'ROLE_MAP_')
    * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_ADMIN')")
    */
   public function getAppusersNotAction($rolePrefix) : Response
   {
     $mapAppUsers = $this->getDoctrine()->getRepository(User::class)->findByRole($rolePrefix, true);

     $serializer = $this->container->get('jms_serializer');
     $serialized = $serializer->serialize($mapAppUsers, 'json');
     $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

     return $response;
   }
}
