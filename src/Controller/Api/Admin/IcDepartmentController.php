<?php

namespace App\Controller\Api\Admin;

use App\Entity\Ic\IcDepartment;
use App\Service\IcService;
use App\Util\RequestHelper;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * CRUD for the departments shared by the Programs and Scholarships apps (ic_departments).
 * Prefix /api/admin/ (routes.yaml); global admins only.
 */
class IcDepartmentController extends AbstractController
{
    public function __construct(
        private IcService $service,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/departments', methods: ['GET'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function listAction(Request $request): Response
    {
        [$page, $limit] = RequestHelper::pagination($request, 50);
        $searchTerm = $request->query->get('searchterm');

        $result = $this->service->getDepartmentsPagination($page, $limit, $searchTerm);
        return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/departments/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function getAction(int $id): Response
    {
        $department = $this->service->getDepartmentWithCounts($id);
        if ($department === null) {
            return new Response(json_encode("Department not found."), 404, ["Content-Type" => "application/json"]);
        }
        return new Response(json_encode($department), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/departments', methods: ['POST'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function createAction(Request $request): Response
    {
        $department = new IcDepartment();
        $this->applyFields($department, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($department);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($department);
        return new Response($this->serialize($department), 201, ["Content-Type" => "application/json"]);
    }

    #[Route('/departments/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function updateAction(int $id, Request $request): Response
    {
        $department = $this->service->getDepartment($id);
        if ($department === null) {
            return new Response(json_encode("Department not found."), 404, ["Content-Type" => "application/json"]);
        }

        $this->applyFields($department, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($department);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($department);
        return new Response($this->serialize($department), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Refuses (409) while any program or scholarship still points at the department.
     */
    #[Route('/departments/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function deleteAction(int $id): Response
    {
        $department = $this->service->getDepartment($id);
        if ($department === null) {
            return new Response(json_encode("Department not found."), 404, ["Content-Type" => "application/json"]);
        }

        $blocker = $this->service->departmentDeleteBlocker($id);
        if ($blocker !== null) {
            return $this->inUse($blocker);
        }

        try {
            $this->service->delete($department);
        } catch (ForeignKeyConstraintViolationException) {
            return $this->inUse(sprintf("%s can't be deleted because it is still in use.", $department->getDepartment()));
        }

        return new Response(null, 204);
    }

    /* ***************************** Helpers ***************************** */

    /**
     * An unknown or blank collegeId leaves the college unset so validation reports it.
     */
    private function applyFields(IcDepartment $department, array $data): void
    {
        if (array_key_exists('department', $data)) {
            $department->setDepartment(trim((string) $data['department']));
        }
        if (array_key_exists('collegeId', $data)) {
            $collegeId = filter_var($data['collegeId'], FILTER_VALIDATE_INT);
            $department->setCollege($collegeId === false ? null : $this->service->getCollege($collegeId));
        }
    }

    private function inUse(string $message): Response
    {
        return new Response(json_encode([
            'error' => 'in_use',
            'message' => $message,
        ]), 409, ["Content-Type" => "application/json"]);
    }

    private function serialize($data): string
    {
        return $this->serializer->serialize($data, "json", ['groups' => 'ic']);
    }
}
