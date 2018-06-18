<?php
namespace App\Controller\Api\Redirect;

use App\Entity\Redirect\Redirect;
use App\Service\RedirectItemService;
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
 * API Redirect Controller
 * This controller manages the redirect items with the actions of getting,
 * adding, updating, and deleting.
 */
class RedirectController extends FOSRestController {
  private $service;

  /**
   * The constructor of the RedirectItemController.
   * @param RedirectItemService $service The service container of this controller.
   */
  public function __construct(RedirectItemService $service) {
    $this->service = $service;
  }

  /**
   * Deletes the redirect item from the specified id.
   * @param string $id The ID of the redirect item.
   * @return Response The message of the deleted item, the status code, and the hTTP headers.
   */
  public function deleteRedirectAction($id): Response {
    $redirectItem = $this->getDoctrine()->getRepository(Redirect::class)->find($id);

    $em = $this->getDoctrine()->getManager();
    $em->remove($redirectItem);
    $em->flush();

    $response = new Response("Item has been deleted.", 204, array("Content-Type" => "application/json"));

    return $response;
  }

  /**
   * Gets the redirect item by the specified ID.
   * @param string $id The ID of the redirect item.
   * @return Response The redirect item, the status code, and the HTTP headers.
   */
  public function getRedirectAction($id): Response {
    $redirectItem = $this->getDoctrine()->getRepository(Redirect::class)->findOneBy(["id" => $id]);

    // Do the following if the redirect item is not found.
    if (!$redirectItem) {
      $response = new Response("The redirect item you requested was not found.", 404, array("Content-Type" => "application/json"));

      return $response;
    }

    $context = new SerializationContext();
    $context->setSerializeNull(true);

    $serializer = $this->container->get("jms_serializer");
    $serialized = $serializer->serialize($redirectItem, "json", $context);
    $response = new Response($serialized, 200, array("Content-Type" => "application/json"));

    return $response;
  }

  /**
   * Gets all redirect items.
   * @return Response All redirects, the status code, and the HTTP headers.
   */
  public function getRedirectsAction(): Response {
    $redirectItems = $this->getDoctrine()->getRepository(Redirect::class)->findBy([], ['fromLink' => 'asc']);
    $serializer = $this->container->get("jms_serializer");
    $serialized = $serializer->serialize($redirectItems, "json");
    $response = new Response($serialized, 200, array("Content-Type" => "application/json"));

    return $response;
  }

  /**
   * Posts the new redirect from the specified request.
   * @param Request $request The holder of the information about the new redirect item.
   * @return Response The redirect, the status code, and the HTTP headers.
   */
  public function postRedirectAction(Request $request): Response {
    $serializer = $this->container->get("jms_serializer");
    $em = $this->getDoctrine()->getManager();
    $redirect = new Redirect();

    /* Formatting fromLink */
    $fromLink = $request->request->get("fromLink");
    $fromLink = substr($fromLink, -1) == "/" ? substr($fromLink, 0, -1) : $fromLink; // Remove "/" if it is the last character.
    $parsedFromLink = parse_url($fromLink);

    if (array_key_exists("host", $parsedFromLink) && preg_match("/emich.edu/", $parsedFromLink["host"])) {
      if ($parsedFromLink["path"][0] != "/") {
        $fromLink = "/" . $parsedFromLink["path"];
      } else {
        $fromLink = $parsedFromLink["path"];
      }
    }

    /* Formatting toLink */
    $toLink = $request->request->get("toLink");
    $toLink = substr($toLink, -1) == "/" ? substr($toLink, 0, -1) : $toLink; // Remove "/" if it is the last character.
    $parsedToLink = parse_url($toLink);

    if (array_key_exists("host", $parsedToLink) && preg_match("/emich.edu/", $parsedToLink["host"])) {
      if ($parsedToLink["path"][0] != "/") {
        $toLink = "/" . $parsedToLink["path"];
      } else {
        $toLink = $parsedToLink["path"];
      }
    }

    // Set the fields for all redirect objects.
    $redirect->setFromLink($fromLink);
    $redirect->setToLink($toLink);
    $redirect->setItemType($request->request->get("itemType"));
    $redirect->setVisits(0);

    // Validate the redirect.
    $errors = $this->service->validate($redirect);

    // Do the following if the redirect is not found.
    if (count($errors) > 0) {
      $serialized = $serializer->serialize($errors, "json");
      $response = new Response($serialized, 422, array("Content-Type" => "application/json"));

      return $response;
    }

    // Persist the redirect.
    $em->persist($redirect);

    // Commit everything to the database.
    $em->flush();

    $serialized = $serializer->serialize($redirect, "json");
    $response = new Response($serialized, 201, array("Content-Type" => "application/json"));

    return $response;
  }

  /**
   * Updates the redirect item from the specified request.
   * @param Request $request The holder of the information about the updated redirect item.
   * @return Response The redirect, the status code, and the HTTP headers.
   */
  public function putRedirectAction(Request $request): Response {
    $serializer = $this->container->get("jms_serializer");
    $em = $this->getDoctrine()->getManager();
    $redirect = $this->getDoctrine()->getRepository(Redirect::class)->find($request->request->get("id"));

    /* Formatting fromLink */
    $fromLink = $request->request->get("fromLink");
    $fromLink = substr($fromLink, -1) == "/" ? substr($fromLink, 0, -1) : $fromLink; // Remove "/" if it is the last character.
    $parsedFromLink = parse_url($fromLink);

    if (array_key_exists("host", $parsedFromLink) && preg_match("/emich.edu/", $parsedFromLink["host"])) {
      if ($parsedFromLink["path"][0] != "/") {
        $fromLink = "/" . $parsedFromLink["path"];
      } else {
        $fromLink = $parsedFromLink["path"];
      }
    }

    /* Formatting toLink */
    $toLink = $request->request->get("toLink");
    $toLink = substr($toLink, -1) == "/" ? substr($toLink, 0, -1) : $toLink; // Remove "/" if it is the last character.
    $parsedToLink = parse_url($toLink);

    if (array_key_exists("host", $parsedToLink) && preg_match("/emich.edu/", $parsedToLink["host"])) {
      if ($parsedToLink["path"][0] != "/") {
        $toLink = "/" . $parsedToLink["path"];
      } else {
        $toLink = $parsedToLink["path"];
      }
    }

    // Set the fields for all redirectItem objects.
    $redirect->setFromLink($fromLink);
    $redirect->setToLink($toLink);

    // Validate the redirect item.
    $errors = $this->service->validate($redirect);

    if (count($errors) > 0) {
      $serialized = $serializer->serialize($errors, "json");
      $response = new Response($serialized, 422, array("Content-Type" => "application/json"));

      return $response;
    }

    // Persist the redirect item.
    $em->persist($redirect);

    // Commit everything to the database.
    $em->flush();

    $serialized = $serializer->serialize($redirect, "json");
    $response = new Response($serialized, 201, array("Content-Type" => "application/json"));

    return $response;
  }
}
?>
