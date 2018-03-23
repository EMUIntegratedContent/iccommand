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
use App\Entity\User;


class UserController extends FOSRestController{

  /**
   * Get all users
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN')")
   */
  public function getUsersAction()
  {
    $users = $this->getDoctrine()->getRepository(User::class)->findBy([],['username' => 'asc']);

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($users, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

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

  /**
   * Update a user's information and roles
   */
   public function putUserAction(Request $request, $username){

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
     $this->syncUserRoles($user, $updatedRoles);
     $this->setUserEnabledStatus($user, $request->request->get('enabled'));

     $userManager->updateUser($user);

     $response = new Response('User ' . $username . ' was updated successfully.', 200, array('Content-Type' => 'application/json'));
     return $response;
   }

   // PUT IN A SERVICE
   public function syncUserRoles(User $user, $updatedRoles){
     $userManager = $this->container->get('fos_user.user_manager');
     $currentRoles = $user->getRoles();

     $rolesToRemove = array_diff($currentRoles, $updatedRoles); // result is the items that do NOT appear in the updated roles array

     // Find and remove any roles that do NOT appear in the updated roles
     foreach($rolesToRemove as $role){
       $user->removeRole($role);
     }
     // Add roles (must be done second!)
     foreach($updatedRoles as $role){
       $user->addRole($role);
     }
     $userManager->updateUser($user);
   }

   // PUT IN A SERVICE
   public function setUserEnabledStatus(User $user, $status){
     $userManager = $this->container->get('fos_user.user_manager');
     // Only change the status if the current and new status are different
     if($user->isEnabled() !== $status){
       $user->setEnabled($status);
       $userManager->updateUser($user);
     }
   }
}
