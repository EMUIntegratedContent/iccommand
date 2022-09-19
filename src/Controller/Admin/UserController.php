<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/users", name="users")
 */
class UserController extends AbstractController
{
  /**
   * @Route("/", name="users_index")
   */
  public function index(ManagerRegistry $doctrine)
  {
    $em = $doctrine->getManager();
    $userRepo = $em->getRepository(User::class);
    $users = $userRepo->findAll();
    $roles = $this->getParameter('security.role_hierarchy.roles');
    return $this->render('admin/users/index.html.twig', [
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
        'user' => $user,
    ]);
  }
}
