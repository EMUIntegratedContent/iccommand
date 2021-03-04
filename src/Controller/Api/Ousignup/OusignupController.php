<?php

namespace App\Controller\Api\Ousignup;

use App\Entity\OuCampusSignups;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\OusignupListService;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;

class OusignupController extends FOSRestController
{
    public function __construct(OusignupListService $service, LoggerInterface $logger)
    {
        $this->service = $service;
        $this->logger = $logger;
    }
    /**
     * @Route("/api/ousignup/ousignup", name="api_ousignup_ousignup")
     */
    public function index(): Response
    {
        $redirects = $this->getDoctrine()->getRepository(OuCampusSignups::class)->findBy([], ['date' => 'desc']);
        $serializer = $this->container->get("jms_serializer");

        $serialized = $serializer->serialize($redirects, "json");

        $response = new Response($serialized, 200, array("Content-Type" => "application/json"));

        return $response;
    }
}
