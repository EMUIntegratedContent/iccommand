<?php

namespace App\Controller\Api\PhotoRequest;

use App\Entity\PhotoRequest\PhotoRequest;
use App\Entity\User;
use App\Service\PhotoRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

if (!ini_get('display_errors')) {
  ini_set('display_errors', '1');
}
error_reporting(E_ALL);

/**
 * API Photo Request Controller
 * This controller manages the photo requests with the actions of getting, adding,
 * updating, and deleting.
 */
class PhotoRequestController extends AbstractFOSRestController
{
  private PhotoRequestService $service;
  private LoggerInterface $logger;
  private ObjectManager $em;
  private SerializerInterface $serializer;

  /**
   * The constructor of the PhotoRequestController.
   * @param PhotoRequestService $service The service container of this controller.
   */
  public function __construct(PhotoRequestService $service, LoggerInterface $logger, ManagerRegistry $doctrine, SerializerInterface $serializer)
  {
    $this->service = $service;
    $this->logger = $logger;
    $this->em = $doctrine->getManager();
    $this->serializer = $serializer;
  }

  /**
   * Get all photo requests
   * @param Request $request
   * @return Response
   */
  #[Route('/list', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_PHOTO_VIEW")'))]
  public function getPhotoRequestsAction(Request $request): Response
  {
    $page = $request->query->get('page') ?? 1;
    $pageSize = $request->query->get('limit') ?? 25;
    $status = $request->query->get('status');
    $category = $request->query->get('category');

    $photoRequests = $this->service->getPhotoRequestsPagination($page, $pageSize, $status, $category);

    $serialized = $this->serializer->serialize($photoRequests, "json", ['groups' => ['photos']]);

    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Filter out photo requests by name or description
   * @param Request $request
   * @return Response
   */
  #[Route('/search', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_PHOTO_VIEW")'))]
  public function searchPhotoRequestsAction(Request $request): Response
  {
    $searchTerm = $request->query->get('searchterm');

    $photoRequests = $this->service->getPhotoRequestsByName($searchTerm);

    $serialized = $this->serializer->serialize($photoRequests, "json", ['groups' => ['photos']]);

    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Get categories with counts
   * @param Request $request
   * @return Response
   */
  #[Route('/categories', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_PHOTO_VIEW")'))]
  public function getCategoriesAction(Request $request): Response
  {
    $status = $request->query->get('status');
    $categories = $this->service->getCategoriesWithCounts($status);

    $serialized = $this->serializer->serialize($categories, "json");
    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Get all users for assignment dropdown
   * @param Request $request
   * @return Response
   */
  #[Route('/users', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_PHOTO_VIEW")'))]
  public function getUsersAction(Request $request): Response
  {
    $currentAssignedUserId = $request->query->get('currentAssignedUserId');

    // Get users with PHOTO_PHOTOG role
    $users = $this->em->getRepository(User::class)->findBy([], ['lastName' => 'ASC', 'username' => 'ASC']);
    $userData = [];
    $currentAssignedUser = null;

    foreach ($users as $user) {
      $hasPhotogRole = in_array('ROLE_PHOTO_PHOTOG', $user->getRoles());

      // Include user if they have PHOTO_PHOTOG role
      if ($hasPhotogRole) {
        $userData[] = [
          'id' => $user->getId(),
          'firstName' => $user->getFirstName(),
          'lastName' => $user->getLastName(),
          'username' => $user->getUsername(),
          'hasPhotogRole' => true
        ];
      }

      // If this is the currently assigned user, store them separately
      if ($currentAssignedUserId && $user->getId() == $currentAssignedUserId) {
        $currentAssignedUser = [
          'id' => $user->getId(),
          'firstName' => $user->getFirstName(),
          'lastName' => $user->getLastName(),
          'username' => $user->getUsername(),
          'hasPhotogRole' => $hasPhotogRole
        ];
      }
    }

    // If there's a currently assigned user who doesn't have PHOTO_PHOTOG role, add them to the list. This user is not available for future assignment.
    if ($currentAssignedUser && !$currentAssignedUser['hasPhotogRole']) {
      $userData[] = $currentAssignedUser;
    }

    $serialized = $this->serializer->serialize($userData, "json");
    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Gets the photo request by the specified ID.
   * @param $id // The ID of the photo request.
   * @return Response The photo request, the status code, and the HTTP headers.
   */
  #[Route('/{id}', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_PHOTO_VIEW")'))]
  public function getPhotoRequestAction($id): Response
  {
    $photoRequest = $this->service->getPhotoRequest($id);
    if (!$photoRequest) {
      return new Response("The photo request you requested was not found.", 404, array("Content-Type" => "application/json"));
    }
    $serialized = $this->serializer->serialize($photoRequest, "json");

    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Posts the new photo request from the specified request.
   * @param Request $request The holder of the information about the new photo request.
   * @return Response The photo request, the status code, and the HTTP headers.
   */
  #[Route('/', methods: ['POST'])]
  public function postPhotoRequestAction(Request $request): Response
  {
    // Check if this is an API request (has API key or API user agent)
    $hasApiKey = $request->headers->get('X-API-Key');
    $userAgent = $request->headers->get('User-Agent');
    $isApiRequest = $hasApiKey || ($userAgent && preg_match('/.*API.*/i', $userAgent));

    // If not an API request, check for proper authentication and roles
    if (!$isApiRequest) {
      $this->denyAccessUnlessGranted('ROLE_GLOBAL_ADMIN');
      $this->denyAccessUnlessGranted('ROLE_PHOTO_ADMIN');
      $this->denyAccessUnlessGranted('ROLE_PHOTO_CREATE');
    }

    $shootType = $request->request->get("shootType");
    $firstName = $request->request->get("firstName");
    $lastName = $request->request->get("lastName");
    $email = $request->request->get("email");
    $phone = $request->request->get("phone");
    $department = $request->request->get("department");

    $photoRequest = new PhotoRequest();
    $photoRequest->setShootType($shootType ?? 'photoshoot');
    $photoRequest->setFirstName($firstName ?? '');
    $photoRequest->setLastName($lastName ?? '');
    $photoRequest->setEmail($email ?? '');
    $photoRequest->setPhone($phone ?? '');
    $photoRequest->setDepartment($department ?? '');
    $photoRequest->setShootName($request->request->get("shootName") ?? '');
    $photoRequest->setPhotoType($request->request->get("photoType") ?? '');
    $photoRequest->setShootDate($request->request->get("shootDate") ? new \DateTime($request->request->get("shootDate")) : null);
    $photoRequest->setStartTime($request->request->get("startTime") ? new \DateTime($request->request->get("startTime")) : null);
    $photoRequest->setEndTime($request->request->get("endTime") ? new \DateTime($request->request->get("endTime")) : null);
    $photoRequest->setLocation($request->request->get("location") ?? '');
    $photoRequest->setDescription($request->request->get("description") ?? '');
    $photoRequest->setPhotoExplaination($request->request->get("photoExplaination") ?? '');
    $photoRequest->setIntendedUse($request->request->get("intendedUse") ?? '');
    $photoRequest->setForUse($request->request->get("forUse") ?? '');
    $photoRequest->setUrl($request->request->get("url") ?? '');
    $photoRequest->setDesigner($request->request->get("designer") ?? '');
    $photoRequest->setCategory($request->request->get("category") ?? '');

    $errors = $this->service->validate($photoRequest); // Validate the photo request.

    if (count($errors) > 0) {
      // Do the following if there is more than one error.
      $serialized = $this->serializer->serialize($errors, "json");

      return new Response($serialized, 422, array("Content-Type" => "application/json"));
    }

    $this->em->persist($photoRequest); // Persist the photo request.
    $this->em->flush(); // Commit everything to the database.

    $serialized = $this->serializer->serialize($photoRequest, "json", ['groups' => ['photos']]);

    return new Response($serialized, 201, array("Content-Type" => "application/json"));
  }

  /**
   * Updates the photo request from the specified request.
   * @param Request $request The holder of the information about the updated photo request.
   * @return Response The photo request, the status code, and the HTTP headers.
   */
  #[Route('/', methods: ['PUT'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_PHOTO_EDIT")'))]
  public function putPhotoRequestAction(Request $request): Response
  {
    $id = $request->request->get("id");
    $shootType = $request->request->get("shootType");
    $firstName = $request->request->get("firstName");
    $lastName = $request->request->get("lastName");
    $email = $request->request->get("email");
    $phone = $request->request->get("phone");
    $department = $request->request->get("department");

    $photoRequest = $this->service->getPhotoRequestEntity($id);
    $photoRequest->setShootType($shootType ?? 'photoshoot');
    $photoRequest->setFirstName($firstName ?? '');
    $photoRequest->setLastName($lastName ?? '');
    $photoRequest->setEmail($email ?? '');
    $photoRequest->setPhone($phone ?? '');
    $photoRequest->setDepartment($department ?? '');
    $photoRequest->setShootName($request->request->get("shootName"));
    $photoRequest->setPhotoType($request->request->get("photoType"));
    $photoRequest->setShootDate($request->request->get("shootDate") ? new \DateTime($request->request->get("shootDate")) : null);
    $photoRequest->setStartTime($request->request->get("startTime") ? new \DateTime($request->request->get("startTime")) : null);
    $photoRequest->setEndTime($request->request->get("endTime") ? new \DateTime($request->request->get("endTime")) : null);
    $photoRequest->setLocation($request->request->get("location"));
    $photoRequest->setDescription($request->request->get("description"));
    $photoRequest->setPhotoExplaination($request->request->get("photoExplaination"));
    $photoRequest->setIntendedUse($request->request->get("intendedUse"));
    $photoRequest->setForUse($request->request->get("forUse"));
    $photoRequest->setUrl($request->request->get("url"));
    $photoRequest->setDesigner($request->request->get("designer"));
    $photoRequest->setCategory($request->request->get("category"));
    $assignedToId = $request->request->get("assignedTo");
    if ($assignedToId) {
      $assignedTo = $this->em->getRepository(User::class)->find($assignedToId);
      $photoRequest->setAssignedTo($assignedTo);
      $photoRequest->setAssigned(1);
    } else {
      $photoRequest->setAssignedTo(null);
      $photoRequest->setAssigned($request->request->get("assigned") ?? 0);
    }
    $photoRequest->setDeclined($request->request->get("declined") ?? 0);
    $photoRequest->setCompleted($request->request->get("completed") ?? 0);
    $photoRequest->setLongStatus($request->request->get("longStatus") ?? '');
    $photoRequest->setStatus($request->request->get("status") ?? '');
    $photoRequest->setEventDesc($request->request->get("eventDesc") ?? '');

    $errors = $this->service->validate($photoRequest); // Validate the photo request.

    if (count($errors) > 0) {
      // Do the following if there is more than one error.
      $serialized = $this->serializer->serialize($errors, "json");

      return new Response($serialized, 422, array("Content-Type" => "application/json"));
    }

    $this->em->persist($photoRequest); // Persist the photo request.
    $this->em->flush(); // Commit everything to the database.

    $serialized = $this->serializer->serialize($this->service->getPhotoRequest($id), "json", ['groups' => ['photos']]);

    return new Response($serialized, 201, array("Content-Type" => "application/json"));
  }

  /**
   * Deletes the photo request from the specified ID.
   * @param $id // The ID of the photo request.
   * @return Response The message of the deleted photo request, the status code, and the HTTP headers.
   */
  #[Route('/{id}', methods: ['DELETE'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_PHOTO_DELETE")'))]
  public function deletePhotoRequestAction($id): Response
  {
    $photoRequest = $this->service->getPhotoRequestEntity($id);
    if (!$photoRequest) {
      return new Response("The photo request you requested was not found.", 404, array("Content-Type" => "application/json"));
    }

    $this->em->remove($photoRequest);
    $this->em->flush();

    return new Response("The photo request was successfully deleted.", 200, array("Content-Type" => "application/json"));
  }
}
