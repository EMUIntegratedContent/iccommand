<?php

namespace App\Controller\Api\Emergency;

use App\Entity\Emergency\EmergencyBanner;
use App\Entity\Emergency\EmergencyNotice;
use App\Service\EmergencyService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * API Emergency Banner and Notices Controller
 * This controller manages the emergency banner and notices with the actions of getting, adding,
 * updating, and deleting.
 */
class EmergencyController extends AbstractFOSRestController
{
  private EmergencyService $service;
  private LoggerInterface $logger;
  private ManagerRegistry $doctrine;
  private EntityManagerInterface $em;
  private SerializerInterface $serializer;

  /**
   * The constructor of the EmergencyController.
   * @param EmergencyService $service The service container of this controller.
   */
  public function __construct(EmergencyService $service, LoggerInterface $logger, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
  {
    $this->service = $service;
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->em = $em;
    $this->serializer = $serializer;
  }

  /**
   * Gets the emergency banner.
   * @return Response Departments, the status code, and the HTTP headers.
   */
  #[Route('/banner', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_EMERGENCY_ADMIN") or is_granted("ROLE_EMERGENCY_VIEW")'))]
  public function getBannerAction(Request $request): Response
  {
    $banner = $this->service->getBanner();

    $serialized = $this->serializer->serialize($banner, "json", ['groups' => 'banner']);

    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }
}
