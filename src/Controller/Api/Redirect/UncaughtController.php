<?php
namespace App\Controller\Api\Redirect;

use App\Entity\Redirect\Uncaught;
use App\Service\RedirectService;
use Doctrine\ORM\PersistentCollection;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\c;
use FOS\RestBundle\View\View;
use Hateoas\HateoasBuilder;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL);

/**
 * API Uncaught Controller
 * This controller manages the redirect items with the actions of getting,
 * adding, updating, and deleting.
 */
class UncaughtController extends AbstractFOSRestController {
  private $service;
  private $logger;

  /**
   * The constructor of the UncaughtController.
   * @param RedirectService $service The service container of this controller.
   */
  public function __construct(RedirectService $service, LoggerInterface $logger) {
    $this->service = $service;
    $this->logger = $logger;
  }

  /**
   * Find an uncaught URL passed from an external source
   * @param Request $request
   * @return Response
   */
  public function getExternalUncaughtAction(Request $request): Response
  {
      $url = $request->query->get('url');

      $uncaught = $this->getDoctrine()->getRepository(Uncaught::class)->findOneBy(['link' => $url]);

      // $this->logger->info('!!! GET /api/external/uncaught is running !!! URL: ' . $url);
      // $memstart = memory_get_peak_usage(true);
      // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

      if (!$uncaught) {
          $response = new Response(json_encode("The uncaught redirect you requested was not found."), 404, array('Content-Type' => 'application/json'));
          return $response;
      }

      $response = new Response(json_encode('There is an uncaught redirect matching ' . $url . '.'), 200, array('Content-Type' => 'application/json'));
      return $response;
  }

  /**
   * Add a new uncaught URL from an external source
   * @Rest\Post("external/uncaught")
   * @param Request $request
   * @return Response
   */
  public function postExternalUncaughtAction(Request $request): Response
  {
      if( !$request->request->get('url') ){
          $response = new Response(json_encode("No URL was specified. Exiting."), 400, array('Content-Type' => 'application/json'));
          return $response;
      }

      $uncaught = new Uncaught();
      $uncaught->setLink($request->request->get('url'));
      $uncaught->setVisits(1);

      // $this->logger->info('!!! POST /api/external/uncaught is running !!! URL: ' . $request->request->get('url'));
      // $memstart = memory_get_peak_usage(true);
      // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

      $em = $this->getDoctrine()->getManager();
      $em->persist($uncaught);
      $em->flush();
      $response = new Response(json_encode('The uncaught URL ' . $request->request->get('url') . ' was added to the database.'), 201, array("Content-Type" => "application/json"));

      return $response;
  }

  /**
   * Increment the number of visits an uncaught URL redirect has received
   * @param Request $request
   * @return Response
   */
  public function putExternalUncaughtincrementAction(Request $request): Response
  {
      $url = $request->request->get('url');

      $uncaught = $this->getDoctrine()->getRepository(Uncaught::class)->findOneBy(['link' => $url]);

      // $this->logger->info('!!! PUT /api/external/uncaughtincrement is running !!! URL: ' . $url);
      // $memstart = memory_get_peak_usage(true);
      // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

      if (!$uncaught) {
          $response = new Response(json_encode("The redirect you requested was not found."), 404, array('Content-Type' => 'application/json'));
          return $response;
      }

      // Increment the number of visits for the redirect.
      $uncaught->setVisits($uncaught->getVisits() + 1);

      $em = $this->getDoctrine()->getManager();
      $em->persist($uncaught);
      $em->flush();

      $response = new Response(json_encode('Incremented visits to uncaught URL ' . $url . '.'), 201, array("Content-Type" => "application/json"));

      return $response;
  }

  /**
   * Deletes the uncaught item from the specified ID.
   * @param string $id The ID of the uncaught item.
   * @return Response The message of the deleted uncaught item, the status code, and the HTTP headers.
   */
  public function deleteUncaughtAction($id): Response {
    $uncaught = $this->getDoctrine()->getRepository(Uncaught::class)->find($id);

    $em = $this->getDoctrine()->getManager();
    $em->remove($uncaught);
    $em->flush();

    $response = new Response("Uncaught item has been deleted.", 204, array("Content-Type" => "application/json"));

    return $response;
  }

  /**
   * Gets all uncaught items.
   * @return Response All uncaught items, the status code, and the HTTP headers.
   */
  public function getUncaughtsAction(): Response {
    $uncaughts = $this->getDoctrine()->getRepository(Uncaught::class)->findBy(["isRecommended" => true], ["visits" => "desc"]);
    $serializer = $this->container->get("jms_serializer");
    $serialized = $serializer->serialize($uncaughts, "json");
    $response = new Response($serialized, 200, array("Content-Type" => "application/json"));

    return $response;
  }

  /**
   * Posts the new uncaught item from the specified request.
   * @param Request $request The holder of the information about the updated uncaught item.
   * @return Response The uncaught item, the status code, and the HTTP headers.
   */
  public function putUncaughtAction(Request $request): Response {
    $serializer = $this->container->get("jms_serializer");
    $em = $this->getDoctrine()->getManager();
    $uncaught = $this->getDoctrine()->getRepository(Uncaught::class)->find($request->request->get("id"));

    // Set the fields for all uncaught items.
    $uncaught->setIsRecommended($request->request->get("isRecommended"));
    $uncaught->setLink($request->request->get("link"));
    $uncaught->setVisits($request->request->get("visits"));

    // Validate the redirect.
    $errors = $this->service->validate($uncaught);

    // Do the following if the uncaught item is not found.
    if (count($errors) > 0) {
      $serialized = $serializer->serialize($errors, "json");
      $response = new Response($serialized, 422, array("Content-Type" => "application/json"));

      return $response;
    }

    // Persist the uncaught item.
    $em->persist($uncaught);

    // Commit everything to the database.
    $em->flush();

    $serialized = $serializer->serialize($uncaught, "json");
    $response = new Response($serialized, 201, array("Content-Type" => "application/json"));

    return $response;
  }
}
