<?php

namespace App\Controller\Api\Directory;

use App\Entity\Directory\Department;
use App\Service\DirectoryService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * API Department DirectoryController
 * This controller manages the directory departments with the actions of getting, adding,
 * updating, and deleting.
 */
class DirectoryController extends AbstractFOSRestController
{
  private DirectoryService $service;
  private LoggerInterface $logger;
  private ManagerRegistry $doctrine;
  private EntityManagerInterface $em;
  private SerializerInterface $serializer;

  /**
   * The constructor of the DepartmentController.
   * @param DirectoryService $service The service container of this controller.
   */
  public function __construct(DirectoryService $service, LoggerInterface $logger, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
  {
    $this->service = $service;
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->em = $em;
    $this->serializer = $serializer;
  }

  /**
   * Deletes the department from the specified ID.
   * @param $id The ID of the department.
   * @return Response The message of the deleted department, the status code, and the HTTP headers.
   */
  #[Route('/{id}', methods: ['DELETE'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_DEPARTMENTS_DELETE")'))]
  public function deleteDepartmentAction($id): Response
  {
    $department = $this->doctrine->getRepository(Department::class)->find($id);

    if (!$department) {
      return new Response("Department not found.", 404, array("Content-Type" => "application/json"));
    }

    $this->em->remove($department);
    $this->em->flush();

    return new Response("Department has been deleted.", 204, array("Content-Type" => "application/json"));
  }

  /**
   * Gets paginated departments.
   * @return Response Departments, the status code, and the HTTP headers.
   */
  #[Route('/list', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_DEPARTMENTS_VIEW")'))]
  public function getDepartmentsAction(Request $request): Response
  {
    $page = $request->query->get('page') ?? 1;
    $pageSize = $request->query->get('limit') ?? 10;
    $searchTerm = $request->query->get('search') ?? '';

    $departments = $this->service->getDepartmentsPagination($page, $pageSize, $searchTerm);

    $serialized = $this->serializer->serialize($departments, "json", ['groups' => 'department']);

    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Search departments by name or search terms
   * @param Request $request
   * @return Response
   */
  #[Route('/search', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_DEPARTMENTS_VIEW")'))]
  public function searchDepartmentsAction(Request $request): Response
  {
    $searchTerm = $request->query->get('searchterm');

    $departments = $this->service->getDepartmentsByName($searchTerm);

    $serialized = $this->serializer->serialize($departments, "json", ['groups' => 'department']);

    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Gets the department by the specified ID.
   * @param $id The ID of the department.
   * @return Response The department, the status code, and the HTTP headers.
   */
  #[Route('/{id}', methods: ['GET'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_DEPARTMENTS_VIEW")'))]
  public function getDepartmentAction($id): Response
  {
    $department = $this->doctrine->getRepository(Department::class)->findOneBy(["id" => $id]);

    if (!$department) {
      return new Response("The department you requested was not found.", 404, array("Content-Type" => "application/json"));
    }

    $serialized = $this->serializer->serialize($department, "json", ['groups' => 'department']);

    return new Response($serialized, 200, array("Content-Type" => "application/json"));
  }

  /**
   * Posts the new department from the specified request.
   * @param Request $request The holder of the information about the new department.
   * @return Response The department, the status code, and the HTTP headers.
   */
  #[Route('/', methods: ['POST'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_DEPARTMENTS_CREATE")'))]
  public function postDepartmentAction(Request $request): Response
  {
    $department = new Department();

    // Set the fields for the department
    $department->setDepartment($request->request->get("department"));
    $searchTerms = $request->request->get("searchTerms");
    if ($searchTerms) {
      // Normalize search terms: split by @@, trim, filter empty, deduplicate, join
      $terms = array_filter(array_map('trim', explode('@@', $searchTerms)));
      $terms = array_unique($terms);
      $searchTerms = implode('@@', $terms);
    }
    $department->setSearchTerms($searchTerms);
    $department->setMapBuildingName($request->request->get("mapBuildingName"));
    $department->setAddress1($request->request->get("address1"));
    $department->setAddress2($request->request->get("address2"));
    $department->setCity($request->request->get("city"));
    $department->setState($request->request->get("state"));
    $department->setZip($request->request->get("zip"));
    $department->setOnCampus($request->request->get("onCampus") == true || $request->request->get("onCampus") == 1);
    $department->setPhone($request->request->get("phone"));
    $department->setPhoneAlt($request->request->get("phoneAlt"));
    $department->setFax($request->request->get("fax"));
    $department->setEmail($request->request->get("email"));
    $department->setWebsite($request->request->get("website"));
    $department->setFacultyList($request->request->get("facultyList"));
    $department->setStaffList($request->request->get("staffList"));

    // Handle map building relationship
    $mapBuildingId = $request->request->get("mapBuilding");
    if ($mapBuildingId) {
      $mapBuilding = $this->doctrine->getRepository(\App\Entity\Map\MapBuilding::class)->find($mapBuildingId);
      if ($mapBuilding) {
        $department->setMapBuilding($mapBuilding);
      }
    }

    $errors = $this->service->validate($department);

    if (count($errors) > 0) {
      $serialized = $this->serializer->serialize($errors, "json", ['groups' => 'department']);
      return new Response($serialized, 422, array("Content-Type" => "application/json"));
    }

    $this->em->persist($department);
    $this->em->flush();

    $serialized = $this->serializer->serialize($department, "json", ['groups' => 'department']);

    return new Response($serialized, 201, array("Content-Type" => "application/json"));
  }

  /**
   * Updates the department from the specified request.
   * @param Request $request The holder of the information about the updated department.
   * @return Response The department, the status code, and the HTTP headers.
   */
  #[Route('/', methods: ['PUT'])]
  #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_DEPARTMENTS_EDIT")'))]
  public function putDepartmentAction(Request $request): Response
  {
    $department = $this->doctrine->getRepository(Department::class)->find($request->request->get("id"));

    if (!$department) {
      return new Response("Department not found.", 404, array("Content-Type" => "application/json"));
    }

    // Set the fields for the department
    $department->setDepartment($request->request->get("department"));
    $searchTerms = $request->request->get("searchTerms");
    if ($searchTerms) {
      // Normalize search terms: split by @@, trim, filter empty, deduplicate, join
      $terms = array_filter(array_map('trim', explode('@@', $searchTerms)));
      $terms = array_unique($terms);
      $searchTerms = implode('@@', $terms);
    }
    $department->setSearchTerms($searchTerms);
    $department->setMapBuildingName($request->request->get("mapBuildingName"));
    $department->setAddress1($request->request->get("address1"));
    $department->setAddress2($request->request->get("address2"));
    $department->setCity($request->request->get("city"));
    $department->setState($request->request->get("state"));
    $department->setZip($request->request->get("zip"));
    $department->setOnCampus($request->request->get("onCampus") == true || $request->request->get("onCampus") == '1');
    $department->setPhone($request->request->get("phone"));
    $department->setPhoneAlt($request->request->get("phoneAlt"));
    $department->setFax($request->request->get("fax"));
    $department->setEmail($request->request->get("email"));
    $department->setWebsite($request->request->get("website"));
    $department->setFacultyList($request->request->get("facultyList"));
    $department->setStaffList($request->request->get("staffList"));

    // Handle map building relationship
    $mapBuildingId = $request->request->get("mapBuilding");
    if ($mapBuildingId) {
      $mapBuilding = $this->doctrine->getRepository(\App\Entity\Map\MapBuilding::class)->find($mapBuildingId);
      if ($mapBuilding) {
        $department->setMapBuilding($mapBuilding);
      }
    } else {
      $department->setMapBuilding(null);
    }

    $errors = $this->service->validate($department);

    if (count($errors) > 0) {
      $serialized = $this->serializer->serialize($errors, "json", ['groups' => 'department']);
      return new Response($serialized, 422, array("Content-Type" => "application/json"));
    }

    $this->em->persist($department);
    $this->em->flush();

    $serialized = $this->serializer->serialize($department, "json", ['groups' => 'department']);

    return new Response($serialized, 201, array("Content-Type" => "application/json"));
  }
}
