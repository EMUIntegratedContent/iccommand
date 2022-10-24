<?php
namespace App\Controller\Api\Redirect;

use App\Entity\Redirect\Redirect;
use App\Entity\Redirect\Uncaught;
use App\Service\RedirectService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Hateoas\HateoasBuilder;
use JMS\Serializer\SerializationContext;
use MongoDB\Driver\Manager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
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
class RedirectController extends AbstractFOSRestController
{
    private RedirectService $service;
    private LoggerInterface $logger;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    /**
     * The constructor of the RedirectController.
     * @param RedirectService $service The service container of this controller.
     */
    public function __construct(RedirectService $service, LoggerInterface $logger, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->service = $service;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * Find a redirect URL passed from an external source
     * @Rest\Get("external/redirect")
     * @param Request $request
     * @return Response
     */
    public function getExternalRedirectAction(Request $request): Response
    {
        $url = $request->query->get('url');

        $redirect = $this->doctrine->getRepository(Redirect::class)->findOneBy(['fromLink' => $url]);

        // $this->logger->info('!!! GET /api/external/redirect is running !!! URL: ' . $url);
        //
        // $memstart = memory_get_peak_usage(true);
        // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

        if (!$redirect) {
            return new Response(json_encode("The redirect you requested was not found."), 404, array('Content-Type' => 'application/json'));
        }

        return new Response(json_encode($redirect->getToLink()), 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Increment the number of visits a URL redirect has received
     * @Rest\Put("external/redirect")
     * @param Request $request
     * @return Response
     */
    public function putExternalRedirectincrementAction(Request $request): Response
    {
        $url = $request->request->get('url');

        $redirect = $this->doctrine->getRepository(Redirect::class)->findOneBy(['fromLink' => $url]);

        // $this->logger->info('!!! PUT /api/external/redirectincrement is running !!! URL: ' . $url);
        //
        // $memstart = memory_get_peak_usage(true);
        // $this->logger->info("PEAK MEMORY: " . $memstart . " bytes.");

        if (!$redirect) {
            return new Response(json_encode("The redirect you requested was not found."), 404, array('Content-Type' => 'application/json'));
        }

        // Increment the number of visits for the redirect.
        $redirect->setVisits($redirect->getVisits() + 1);
        $redirect->setLastVisit(new \DateTime());

        $this->em->persist($redirect);
        $this->em->flush();

        return new Response('Incremented visits to URL ' . $url . '.', 201, array("Content-Type" => "application/json"));
    }

    /**
     * Deletes the redirect from the specified ID.
     * @Rest\Delete("/{id}")
     * @param $id  // The ID of the redirect.
     * @return Response The message of the deleted redirect, the status code, and the HTTP headers.
     */
    public function deleteRedirectAction($id): Response
    {
        $redirect = $this->doctrine->getRepository(Redirect::class)->find($id);

        $this->em->remove($redirect);
        $this->em->flush();

        return new Response("Redirect has been deleted.", 204, array("Content-Type" => "application/json"));
    }

    /**
     * Gets the redirect by the specified ID.
     * @param $id // The ID of the redirect.
     * @Rest\Get("/{id}")
     * @return Response The redirect, the status code, and the HTTP headers.
     */
    public function getRedirectAction($id): Response
    {
        $redirect = $this->doctrine->getRepository(Redirect::class)->findOneBy(["id" => $id]);

        if (!$redirect) {
            // Do the following if the redirect is not found.
            return new Response("The redirect you requested was not found.", 404, array("Content-Type" => "application/json"));
        }

        $serialized = $this->serializer->serialize($redirect, "json", ['groups' => 'redir']);

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Gets all redirects.
     * @Rest\Get("/")
     * @return Response All redirects, the status code, and the HTTP headers.
     */
    public function getRedirectsAction(): Response
    {
        $redirects = $this->doctrine->getRepository(Redirect::class)->findBy([], ['fromLink' => 'asc']);

        $serialized = $this->serializer->serialize($redirects, "json", ['groups' => 'redir']);

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Posts the new redirect from the specified request.
     * @param Request $request The holder of the information about the new redirect.
     * @Rest\Post("/")
     * @return Response The redirect, the status code, and the HTTP headers.
     */
    public function postRedirectAction(Request $request): Response
    {
        $redirect = new Redirect();

        /* Formatting fromLink */
        $fromLink = $request->request->get("fromLink");
        $fromLink = preg_replace("/ /", "%20", $fromLink); // Replaces " " with "%20"
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
            && (($parsedToLink["host"] == "www.emich.edu") || ($parsedToLink["host"] == "emich.edu"))) {
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
            $serialized = $this->serializer->serialize($errors, "json", ['groups' => 'redir']);

            return new Response($serialized, 422, array("Content-Type" => "application/json"));
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

        $this->em->persist($redirect); // Persist the redirect.
        $this->em->flush(); // Commit everything to the database.

        $serialized = $this->serializer->serialize($redirect, "json", ['groups' => 'redir']);

        return new Response($serialized, 201, array("Content-Type" => "application/json"));
    }

    /**
     * Updates the redirect from the specified request.
     * @param Request $request The holder of the information about the updated redirect.
     * @Rest\Put()
     * @return Response The redirect, the status code, and the HTTP headers.
     */
    public function putRedirectAction(Request $request): Response
    {
        $redirect = $this->doctrine->getRepository(Redirect::class)->find($request->request->get("id"));

        /* Formatting fromLink */
        $fromLink = $request->request->get("fromLink");
        $fromLink = preg_replace("/ /", "%20", $fromLink); // Replaces " " with "%20"
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
            && (($parsedToLink["host"] == "www.emich.edu") || ($parsedToLink["host"] == "emich.edu"))) {
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
            $serialized = $this->serializer->serialize($errors, "json", ['groups' => 'redir']);

            return new Response($serialized, 422, array("Content-Type" => "application/json"));
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
                $serialized = $this->serializer->serialize($message, "json", ['groups' => 'redir']);

                return new Response($serialized, 422, array("Content-Type" => "application/json"));
            }

            // Check if the toLink has any spaces.
            if ($toLink != trim($toLink)) {
                $message = $redirect->getItemType() == "redirect of broken link"
                    ? "The actual link should not include any spaces." : "The full link should not include any spaces.";

                return new Response($message, 422, array("Content-Type" => "application/json"));
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

        $this->em->persist($redirect); // Persist the redirect.
        $this->em->flush(); // Commit everything to the database.

        $serialized = $this->serializer->serialize($redirect, "json", ['groups' => 'redir']);

        return new Response($serialized, 201, array("Content-Type" => "application/json"));
    }

    /**
     * Updates the redirects specifically checking for the toLink fields to be valid URLs.
     * @return Response The redirects, the status code, and the HTTP headers.
     */
    public function putRedirectsAction(): Response
    {
        $redirects = $this->doctrine->getRepository(Redirect::class)->findBy([], ['fromLink' => 'asc']);

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
                    && (($parsedToLink["host"] == "www.emich.edu") || ($parsedToLink["host"] == "emich.edu"))) {
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

            $this->em->persist($redirects[$i]); // Persist the redirect.
            $this->em->flush(); // Commit everything to the database.
        }

        $serialized = $this->serializer->serialize($redirects, "json", ['groups' => 'redir']);

        return new Response($serialized, 201, array("Content-Type" => "application/json"));
    }
}

?>
