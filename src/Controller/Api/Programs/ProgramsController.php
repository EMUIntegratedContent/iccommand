<?php
namespace App\Controller\Api\Programs;

use App\Service\ProgramsService;
use App\Entity\Programs\Programs;
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
        $pageSize = $request->query->get('limit') ?? 25;
        $catalog = $request->query->get('catalog') ?? 'undergraduate';

        $programs = $this->service->getProgramsPagination($page, $pageSize, $catalog);

        $serialized = $this->serializer->serialize($programs, "json");

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Filter out programs by name
     * @param Request $request
     * @return Response
     */
    #[Route('/search', methods: ['GET'])]
    public function searchProgramsAction(Request $request): Response
    {
        $searchTerm = $request->query->get('searchterm');
        $catalog = $request->query->get('catalog') ?? 'undergraduate';

        $programs = $this->service->getProgramsByName($searchTerm, $catalog);

        $serialized = $this->serializer->serialize($programs, "json");

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Get all program websites
     * @param Request $request
     * @return Response
     */
    #[Route('/websites', methods: ['GET'])]
    public function getWebsitesAction(Request $request): Response
    {
        $page = $request->query->get('page') ?? 1;
        $pageSize = $request->query->get('limit') ?? 25;

        $websites = $this->service->getWebsitesPagination($page, $pageSize);

        $serialized = $this->serializer->serialize($websites, "json");

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Filter out program websites by name
     * @param Request $request
     * @return Response
     */
    #[Route('/searchwebsites', methods: ['GET'])]
    public function searchWebsitesAction(Request $request): Response
    {
        $searchTerm = $request->query->get('searchterm');

        $websites = $this->service->getWebsitesByProg($searchTerm);

        $serialized = $this->serializer->serialize($websites, "json");

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Gets the program by the specified ID.
     * @param $id // The ID of the program.
     * @return Response The program, the status code, and the HTTP headers.
     */
    #[Route('/{id}', methods: ['GET'])]
    public function getRedirectAction($id): Response
    {
        $program = $this->doctrine->getRepository(Programs::class)->findOneBy(["id" => $id]);

        if (!$program) {
            // Do the following if the program is not found.
            return new Response("The program you requested was not found.", 404, array("Content-Type" => "application/json"));
        }

        $serialized = $this->serializer->serialize($program, "json");

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }
}

?>
