<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/admin", name="users")
 */
class AdminController extends Controller
{
  /**
   * @Route("/", name="admin_index")
   */
  public function index()
  {
    $roles = $this->container->getParameter('security.role_hierarchy.roles');
    $userManager = $this->container->get('fos_user.user_manager');
    $users = $userManager->findUsers();
    return $this->render('admin/index.html.twig', []);
  }
}
