<?php
namespace App\Controller\Applinks;

use App\Entity\Redirect\Redirect;
use App\Service\RedirectService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the redirects.
 */
class ApplinksController extends AbstractController {
  private RedirectService $service;
  private ManagerRegistry $doctrine;

  /**
   * The constructor of the controller for the redirects.
   * @param RedirectService $service The service of the redirects.
   */
  public function __construct(RedirectService $service, ManagerRegistry $doctrine)
  {
    $this->service = $service;
    $this->doctrine = $doctrine;
  }

  /**
   * The index page of the app links.
   * @Route("/applinks", name="applinks_index")
   */
  public function index(): Response
  {
    return $this->render('applinks/index.html.twig', []);
  }
}
