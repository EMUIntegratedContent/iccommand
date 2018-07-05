<?php
namespace App\Controller\Api\Redirect;

use App\Entity\Redirect\Uncaught;
use App\Service\RedirectService;
use Doctrine\ORM\PersistentCollection;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Hateoas\HateoasBuilder;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Uncaught Controller
 * This controller manages the redirect items with the actions of getting,
 * adding, updating, and deleting.
 */
class UncaughtController extends FOSRestController {
  private $service;

  /**
   * The constructor of the UncaughtController.
   * @param RedirectService $service The service container of this controller.
   */
  public function __construct(RedirectService $service) {
    $this->service = $service;
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
