<?php

namespace App\Controller\Emergency;

use App\Service\EmergencyService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for emergency banners and notices.
 */
class EmergencyController extends AbstractController
{
  private EmergencyService $service;
  private ManagerRegistry $doctrine;

  /**
   * The constructor of the controller for emergency banners and notices.
   * @param EmergencyService $service The service of the photo requests.
   */
  public function __construct(EmergencyService $service, ManagerRegistry $doctrine)
  {
    $this->service = $service;
    $this->doctrine = $doctrine;
  }

  /**
   * The index page of the emergency banners and notices.
   */
  #[Route('/emergency', name: 'emergency_index')]
  public function index(): Response
  {
    $permissions = json_encode($this->service->getEmergencyAppPermissions());
    return $this->render('emergency/index.html.twig', [
      'permissions' => $permissions,
      'controller_name' => 'Emergency'
    ]);
  }

  /**
   * The management page of the emergency banner app.
   */
  #[Route('/emergency/manage', name: 'emergency_manage')]
  public function manage(): Response
  {
    return $this->render('emergency/manage.html.twig', []);
  }
}
