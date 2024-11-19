<?php
namespace App\Controller\Api\Programs;

use App\Service\ProgramsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL);

/**
 * API Redirect Controller
 * This controller manages the redirects with the actions of getting, adding,
 * updating, and deleting.
 */
class ProgramsController extends AbstractFOSRestController
{
    private ProgramsService $service;
    private LoggerInterface $logger;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    /**
     * The constructor of the ProgramsController.
     * @param ProgramsService $service The service container of this controller.
     */
    public function __construct(ProgramsService $service, LoggerInterface $logger, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->service = $service;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * Get all programs
     * @param Request $request
     * @return Response
     */
    #[Route('/list', methods: ['GET'])]
    public function getProgramsAction(Request $request): Response
    {
        $page = $request->query->get('page') ?? 1;
        $pageSize = $request->query->get('limit') ?? 10;

        $redirects = $this->service->getProgramsPagination($page, $pageSize);

        $serialized = $this->serializer->serialize($redirects, "json");

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
//        $programs = $this->doctrine->getRepository(Programs::class)->findBy([], ['program' => 'asc']);
//
//        $serialized = $this->serializer->serialize($programs, 'json', []);
//        return new Response($serialized, 200, ['Content-Type' => 'application/json']);
    }
}

?>
