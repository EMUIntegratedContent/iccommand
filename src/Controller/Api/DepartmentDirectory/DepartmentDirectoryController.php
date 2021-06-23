<?php

namespace App\Controller\Api\DepartmentDirectory;

use App\Entity\DirectoryDepartments;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\DepartmentDirectoryService;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;

class DepartmentDirectoryController extends FOSRestController
{
    public function __construct(DepartmentDirectoryService $service, LoggerInterface $logger)
    {
        $this->service = $service;
        $this->logger = $logger;
    }
    /**
     * @Route("/api/departmentdirectory", name="api_department_directory_department_directory")
     */
    public function index(): Response
    {
        $redirects = $this->getDoctrine()->getRepository(DirectoryDepartments::class)->findBy([], ['id' => 'asc']);
        $serializer = $this->container->get("jms_serializer");

        $serialized = $serializer->serialize($redirects, "json");

        $response = new Response($serialized, 200, array("Content-Type" => "application/json"));

        return $response;
    }
}
