<?php
namespace App\Controller\Api\Redirect;

use App\Entity\Redirect\Redirect;
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
 * API Redirect Controller
 * This controller manages the redirects with the actions of getting, adding,
 * updating, and deleting.
 */
class RedirectController extends FOSRestController
{
    private $service;

    /**
     * The constructor of the RedirectController.
     * @param RedirectService $service The service container of this controller.
     */
    public function __construct(RedirectService $service)
    {
        $this->service = $service;
    }

    /**
     * Deletes the redirect from the specified ID.
     * @param string $id The ID of the redirect.
     * @return Response The message of the deleted redirect, the status code, and the HTTP headers.
     */
    public function deleteRedirectAction($id): Response
    {
        $redirect = $this->getDoctrine()->getRepository(Redirect::class)->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($redirect);
        $em->flush();

        $response = new Response("Redirect has been deleted.", 204, array("Content-Type" => "application/json"));

        return $response;
    }

    /**
     * Gets the redirect by the specified ID.
     * @param string $id The ID of the redirect.
     * @return Response The redirect, the status code, and the HTTP headers.
     */
    public function getRedirectAction($id): Response
    {
        $redirect = $this->getDoctrine()->getRepository(Redirect::class)->findOneBy(["id" => $id]);
        $serializer = $this->container->get("jms_serializer");

        if (!$redirect) {
            // Do the following if the redirect is not found.
            $response = new Response("The redirect you requested was not found.", 404, array("Content-Type" => "application/json"));

            return $response;
        }

        $context = new SerializationContext();
        $context->setSerializeNull(true);

        $serialized = $serializer->serialize($redirect, "json", $context);

        $response = new Response($serialized, 200, array("Content-Type" => "application/json"));

        return $response;
    }

    /**
     * Gets all redirects.
     * @return Response All redirects, the status code, and the HTTP headers.
     */
    public function getRedirectsAction(): Response
    {
        $redirects = $this->getDoctrine()->getRepository(Redirect::class)->findBy([], ['fromLink' => 'asc']);
        $serializer = $this->container->get("jms_serializer");

        $serialized = $serializer->serialize($redirects, "json");

        $response = new Response($serialized, 200, array("Content-Type" => "application/json"));

        return $response;
    }

    /**
     * Posts the new redirect from the specified request.
     * @param Request $request The holder of the information about the new redirect.
     * @return Response The redirect, the status code, and the HTTP headers.
     */
    public function postRedirectAction(Request $request): Response
    {
        $redirect = new Redirect();
        $serializer = $this->container->get("jms_serializer");
        $em = $this->getDoctrine()->getManager();

        /* Formatting fromLink */

        $fromLink = $request->request->get("fromLink");
        $fromLink = substr($fromLink, -1) == "/" ? substr($fromLink, 0, -1) : $fromLink; // Remove "/" if it is the last character.
        $parsedFromLink = parse_url($fromLink);

        if (array_key_exists("host", $parsedFromLink) && preg_match("/emich\.edu/", $parsedFromLink["host"])) {
            if ($parsedFromLink["path"][0] != "/") {
                $fromLink = "/" . $parsedFromLink["path"];
            } else {
                $fromLink = $parsedFromLink["path"];
            }
        } else if (!array_key_exists("host", $parsedFromLink) && $parsedFromLink["path"][0] != "/") {
            $fromLink = "/" . $parsedFromLink["path"];
        }

        /* Formatting toLink */

        $toLink = $request->request->get("toLink");
        $toLink = substr($toLink, -1) == "/" ? substr($toLink, 0, -1) : $toLink; // Remove "/" if it is the last character.
        $parsedToLink = parse_url($toLink);

        if (array_key_exists("host", $parsedToLink)
            && array_key_exists("path", $parsedToLink)
            && preg_match("/emich\.edu/", $parsedToLink["host"])) {
            if ($parsedToLink["path"][0] != "/") {
                $toLink = "/" . $parsedToLink["path"];
            } else {
                $toLink = $parsedToLink["path"];
            }
        } else if (!array_key_exists("host", $parsedToLink)
            && array_key_exists("path", $parsedToLink)
            && $parsedToLink["path"][0] != "/") {
            $toLink = "/" . $parsedToLink["path"];
        }

        // Set the fields for all redirects.
        $redirect->setFromLink($fromLink);
        $redirect->setToLink($toLink);
        $redirect->setItemType($request->request->get("itemType"));
        $redirect->setVisits(0);

        $errors = $this->service->validate($redirect); // Validate the redirect.

        if (count($errors) > 0) {
            // Do the following if there is more than one error.
            $serialized = $serializer->serialize($errors, "json");

            $response = new Response($serialized, 422, array("Content-Type" => "application/json"));

            return $response;
        }

        /* Validation of toLink */

        if ($redirect->getItemType() != "invalid redirect of broken link"
            && $redirect->getItemType() != "invalid redirect of shortened link") {
            // Check if the toLink is a valid URL if it is supposed to be a valid redirect.
            $fullToLink = $toLink[0] == "/" ? "https://www.emich.edu$toLink" : $toLink;

            if (get_headers($fullToLink, 1)[0] == "HTTP/1.1 404 Not Found") {
                $message = $redirect->getItemType() == "redirect of broken link"
                    ? "The actual link is not valid." : "The full link is not valid.";
                $response = new Response($message, 422, array("Content-Type" => "application/json"));

                return $response;
            }

            if ($toLink != trim($toLink)) {
                // Check if the toLink has any spaces.
                $message = $redirect->getItemType() == "redirect of broken link"
                    ? "The actual link should not include any spaces." : "The full link should not include any spaces.";

                $response = new Response($message, 422, array("Content-Type" => "application/json"));

                return $response;
            }
        }


        $em->persist($redirect); // Persist the redirect.
        $em->flush(); // Commit everything to the database.

        $serialized = $serializer->serialize($redirect, "json");

        $response = new Response($serialized, 201, array("Content-Type" => "application/json"));

        return $response;
    }

    /**
     * Updates the redirect from the specified request.
     * @param Request $request The holder of the information about the updated redirect.
     * @return Response The redirect, the status code, and the HTTP headers.
     */
    public function putRedirectAction(Request $request): Response
    {
        $redirect = $this->getDoctrine()->getRepository(Redirect::class)->find($request->request->get("id"));
        $serializer = $this->container->get("jms_serializer");
        $em = $this->getDoctrine()->getManager();

        /* Formatting fromLink */

        $fromLink = $request->request->get("fromLink");
        $fromLink = substr($fromLink, -1) == "/" ? substr($fromLink, 0, -1) : $fromLink; // Remove "/" if it is the last character.
        $parsedFromLink = parse_url($fromLink);

        if (array_key_exists("host", $parsedFromLink) && preg_match("/emich\.edu/", $parsedFromLink["host"])) {
            if ($parsedFromLink["path"][0] != "/") {
                $fromLink = "/" . $parsedFromLink["path"];
            } else {
                $fromLink = $parsedFromLink["path"];
            }
        } else if (!array_key_exists("host", $parsedFromLink) && $parsedFromLink["path"][0] != "/") {
            $fromLink = "/" . $parsedFromLink["path"];
        }

        /* Formatting toLink */

        $toLink = $request->request->get("toLink");
        $toLink = substr($toLink, -1) == "/" ? substr($toLink, 0, -1) : $toLink; // Remove "/" if it is the last character.
        $parsedToLink = parse_url($toLink);

        if (array_key_exists("host", $parsedToLink)
            && array_key_exists("path", $parsedToLink)
            && preg_match("/emich\.edu/", $parsedToLink["host"])) {
            if ($parsedToLink["path"][0] != "/") {
                $toLink = "/" . $parsedToLink["path"];
            } else {
                $toLink = $parsedToLink["path"];
            }
        } else if (!array_key_exists("host", $parsedToLink)
            && array_key_exists("path", $parsedToLink)
            && $parsedToLink["path"][0] != "/") {
            $toLink = "/" . $parsedToLink["path"];
        }

        // Set the fields for all redirect objects.
        $redirect->setFromLink($fromLink);
        $redirect->setToLink($toLink);
        $redirect->setItemType($request->request->get("itemType"));

        $errors = $this->service->validate($redirect); // Validate the redirect.

        if (count($errors) > 0) {
            // Do the following if there is more than one error.
            $serialized = $serializer->serialize($errors, "json");

            $response = new Response($serialized, 422, array("Content-Type" => "application/json"));

            return $response;
        }

        if ($redirect->getItemType() != "invalid redirect of broken link"
            && $redirect->getItemType() != "invalid redirect of shortened link"
            && $redirect->getItemType() != "expired redirect of broken link"
            && $redirect->getItemType() != "expired redirect of shortened link") {
            // Check if the toLink is a valid URL if it is supposed to be a valid redirect.
            $fullToLink = $toLink[0] == "/" ? "https://www.emich.edu$toLink" : $toLink;

            if (get_headers($fullToLink, 1)[0] == "HTTP/1.1 404 Not Found") {
                $message = $redirect->getItemType() == "redirect of broken link"
                    ? "The actual link is not valid." : "The full link is not valid.";

                $response = new Response($message, 422, array("Content-Type" => "application/json"));

                return $response;
            }

            // Check if the toLink has any spaces.
            if ($toLink != trim($toLink)) {
                $message = $redirect->getItemType() == "redirect of broken link"
                    ? "The actual link should not include any spaces." : "The full link should not include any spaces.";

<<<<<<< HEAD
                $response = new Response($message, 422, array("Content-Type" => "application/json"));
=======
  /**
   * Updates the redirects specifically checking for the toLink fields to be valid URLs.
   * @return Response The redirects, the status code, and the HTTP headers.
   */
  public function putRedirectsAction(Request $request): Response {
    // $selectedRedirects = $this->getDoctrine()->getRepository(Redirect::class)->find($request->request->get("id"));

    $redirects = $this->getDoctrine()->getRepository(Redirect::class)->findBy([], ['fromLink' => 'asc']);
    $serializer = $this->container->get("jms_serializer");
    $em = $this->getDoctrine()->getManager();
>>>>>>> 2d5c14296958f789a183fd066e1daa225b7760db

                return $response;
            }
        } else if ($redirect->getItemType() == "invalid redirect of broken link"
            || $redirect->getItemType() == "invalid redirect of shortened link") {

            /* Validation of toLink */

            $fullToLink = $redirect->getToLink()[0] == "/"
                ? "https://www.emich.edu" . $redirect->getToLink() : $redirect->getToLink();

            if (get_headers($fullToLink, 1)[0] != "HTTP/1.1 404 Not Found") {
                $redirect->setItemType(preg_replace("/invalid /", "", $redirect->getItemType())); // Some broken redirects may be fixed.
            }
        }

        $em->persist($redirect); // Persist the redirect.
        $em->flush(); // Commit everything to the database.

        $serialized = $serializer->serialize($redirect, "json");

        $response = new Response($serialized, 201, array("Content-Type" => "application/json"));

        return $response;
    }

    /**
     * Updates the redirects specifically checking for the toLink fields to be valid URLs.
     * @return Response The redirects, the status code, and the HTTP headers.
     */
    public function putRedirectsAction(): Response
    {
        $redirects = $this->getDoctrine()->getRepository(Redirect::class)->findBy([], ['fromLink' => 'asc']);
        $serializer = $this->container->get("jms_serializer");
        $em = $this->getDoctrine()->getManager();

        for ($i = 0; $i < count($redirects); $i++) {
            // Check to see if the toLink is a valid URL if it is supposed to be a valid redirect.
            if ($redirects[$i]->getItemType() != "invalid redirect of broken link"
                && $redirects[$i]->getItemType() != "invalid redirect of shortened link"
                && $redirects[$i]->getItemType() != "expired redirect of broken link"
                && $redirects[$i]->getItemType() != "expired redirect of shortened link") {

                /* Formatting toLink for Later Use */

                $toLink = $redirects[$i]->getToLink();
                $toLink = substr($toLink, -1) == "/" ? substr($toLink, 0, -1) : $toLink; // Remove "/" if it is the last character.
                $parsedToLink = parse_url($toLink);

                if (array_key_exists("host", $parsedToLink)
                    && array_key_exists("path", $parsedToLink)
                    && preg_match("/emich\.edu/", $parsedToLink["host"])) {
                    if ($parsedToLink["path"][0] != "/") {
                        $toLink = "/" . $parsedToLink["path"];
                    } else {
                        $toLink = $parsedToLink["path"];
                    }
                } else if (!array_key_exists("host", $parsedToLink)
                    && array_key_exists("path", $parsedToLink)
                    && $parsedToLink["path"][0] != "/") {
                    $toLink = "/" . $parsedToLink["path"];
                }

                $redirects[$i]->setToLink($toLink);

                /* Validation of toLink */

                $fullToLink = $redirects[$i]->getToLink()[0] == "/"
                    ? "https://www.emich.edu" . $redirects[$i]->getToLink() : $redirects[$i]->getToLink();

                if (get_headers($fullToLink, 1)[0] == "HTTP/1.1 404 Not Found") {
                    $redirects[$i]->setItemType("invalid " . $redirects[$i]->getItemType()); // Some redirects may be broken later on.
                }
            } else if ($redirects[$i]->getItemType() == "invalid redirect of broken link"
                || $redirects[$i]->getItemType() == "invalid redirect of shortened link") {
                $fullToLink = $redirects[$i]->getToLink()[0] == "/"
                    ? "https://www.emich.edu" . $redirects[$i]->getToLink() : $redirects[$i]->getToLink();

                if (get_headers($fullToLink, 1)[0] != "HTTP/1.1 404 Not Found") {
                    $redirects[$i]->setItemType(preg_replace("/invalid /", "", $redirects[$i]->getItemType())); // Some broken redirects may be fixed.
                }
            }

            $em->persist($redirects[$i]); // Persist the redirect.
            $em->flush(); // Commit everything to the database.
        }

        $serialized = $serializer->serialize($redirects, "json");

        $response = new Response($serialized, 201, array("Content-Type" => "application/json"));

        return $response;
    }
}

?>
