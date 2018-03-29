<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/admin/users", name="users")
 */
class UserController extends Controller
{
  /**
   * @Route("/", name="users_index")
   */
  public function index()
  {
    $roles = $this->container->getParameter('security.role_hierarchy.roles');
    $userManager = $this->container->get('fos_user.user_manager');
    $users = $userManager->findUsers();
    return $this->render('admin/users/index.html.twig', [
        'controller_name' => 'PermissionController',
        'roles' => $roles,
        'users' => $users,
    ]);
  }

  /**
   * @Route("/{username}", name="user_show")
   */
  public function show($username){
    $userManager = $this->container->get('fos_user.user_manager');
    $user = $userManager->findUserByUsername($username);
    if(!$user){
      throw $this->createNotFoundException('The user name ' . $username . ' was not found.');
    }
    return $this->render('admin/users/show.html.twig', [
        'controller_name' => 'PermissionController',
        'user' => $user,
    ]);
  }
}
