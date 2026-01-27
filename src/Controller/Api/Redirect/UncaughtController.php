<?php
namespace App\Controller\Api\Redirect;

use App\Entity\Redirect\Uncaught;
use App\Service\RedirectService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL);

/**
 * API Uncaught Controller
 * This controller manages the redirect items with the actions of getting,
 * adding, updating, and deleting.
 */
class UncaughtController extends AbstractController {
    private RedirectService $service;
    private LoggerInterface $logger;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    /**
     * The constructor of the UncaughtController.
     * @param RedirectService $service The service container of this controller.
     */
    public function __construct(RedirectService $service, LoggerInterface $logger, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer) {
        $this->service = $service;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * Find an uncaught URL passed from an external source
     * @param Request $request
     * @return Response
     */
		#[Route('external/uncaught', methods: ['GET'])]
    public function getExternalUncaughtAction(Request $request): Response
    {
        $url = $request->query->get('url');

        $uncaught = $this->doctrine->getRepository(Uncaught::class)->findOneBy(['link' => $url]);

        // $this->logger->info('!!! GET /api/external/uncaught is running !!! URL: ' . $url);
        // $memstart = memory_get_peak_usage(true);
        // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

        if (!$uncaught) {
            return new Response(json_encode("The uncaught redirect you requested was not found."), 404, array('Content-Type' => 'application/json'));
        }

        return new Response(json_encode('There is an uncaught redirect matching ' . $url . '.'), 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Add a new uncaught URL from an external source
     * @param Request $request
     * @return Response
     */
		#[Route('external/uncaught', methods: ['POST'])]
    public function postExternalUncaughtAction(Request $request): Response
    {
        if(!$request->request->get('url') ){
            return new Response(json_encode("No URL was specified. Exiting."), 400, array('Content-Type' => 'application/json'));
        }

        $uncaught = new Uncaught();
        $uncaught->setLink($request->request->get('url'));
        $uncaught->setVisits(1);

        // $this->logger->info('!!! POST /api/external/uncaught is running !!! URL: ' . $request->request->get('url'));
        // $memstart = memory_get_peak_usage(true);
        // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

        $this->em->persist($uncaught);
        $this->em->flush();
        return new Response(json_encode('The uncaught URL ' . $request->request->get('url') . ' was added to the database.'), 201, array("Content-Type" => "application/json"));
    }

    /**
     * Increment the number of visits an uncaught URL redirect has received
     * @param Request $request
     * @return Response
     */
		#[Route('external/uncaught', methods: ['PUT'])]
    public function putExternalUncaughtincrementAction(Request $request): Response
    {
        $url = $request->request->get('url');

        $uncaught = $this->doctrine->getRepository(Uncaught::class)->findOneBy(['link' => $url]);

        // $this->logger->info('!!! PUT /api/external/uncaughtincrement is running !!! URL: ' . $url);
        // $memstart = memory_get_peak_usage(true);
        // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

        if (!$uncaught) {
            return new Response(json_encode("The redirect you requested was not found."), 404, array('Content-Type' => 'application/json'));
        }

        // Increment the number of visits for the redirect.
        $uncaught->setVisits($uncaught->getVisits() + 1);

        $this->em->persist($uncaught);
        $this->em->flush();

        return new Response(json_encode('Incremented visits to uncaught URL ' . $url . '.'), 201, array("Content-Type" => "application/json"));
    }

    /**
     * Deletes the uncaught item from the specified ID.
     * @param $id // The ID of the uncaught item.
     * @return Response The message of the deleted uncaught item, the status code, and the HTTP headers.
     */
		#[Route('/{id}', methods: ['DELETE'])]
    public function deleteUncaughtAction($id): Response {
        $uncaught = $this->doctrine->getRepository(Uncaught::class)->find($id);

        $this->em->remove($uncaught);
        $this->em->flush();

        return new Response("Uncaught item has been deleted.", 204, array("Content-Type" => "application/json"));
    }

    /**
     * Gets all uncaught items.
     * @return Response All uncaught items, the status code, and the HTTP headers.
     */
		#[Route('/', methods: ['GET'])]
    public function getUncaughtsAction(): Response {
        $uncaughts = $this->doctrine->getRepository(Uncaught::class)->findBy(["isRecommended" => true], ["visits" => "desc"]);
        $serialized = $this->serializer->serialize($uncaughts, "json", ['groups' => 'redir']);
        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Puts the new uncaught item from the specified request.
     * @param Request $request The holder of the information about the updated uncaught item.
     * @return Response The uncaught item, the status code, and the HTTP headers.
     */
		#[Route('/', methods: ['PUT'])]
    public function putUncaughtAction(Request $request): Response {
        $uncaught = $this->doctrine->getRepository(Uncaught::class)->find($request->request->get("id"));

        // Set the fields for all uncaught items.
        $uncaught->setIsRecommended($request->request->get("isRecommended"));
        $uncaught->setLink($request->request->get("link"));
        $uncaught->setVisits($request->request->get("visits"));

        // Validate the redirect.
        $errors = $this->service->validate($uncaught);

        // Do the following if the uncaught item is not found.
        if (count($errors) > 0) {
            $serialized = $this->serializer->serialize($errors, "json");
            return new Response($serialized, 422, array("Content-Type" => "application/json"));
        }

        // Persist the uncaught item.
        $this->em->persist($uncaught);

        // Commit everything to the database.
        $this->em->flush();

        $serialized = $this->serializer->serialize($uncaught, "json", ['groups' => 'redir']);
        return new Response($serialized, 201, array("Content-Type" => "application/json"));
    }
}
