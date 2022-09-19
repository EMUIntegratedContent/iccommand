<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin", name="users")
 */
class AdminController extends AbstractController
{
  /**
   * @Route("/", name="admin_index")
   */
  public function index()
  {
    return $this->render('admin/index.html.twig', []);
  }
}
