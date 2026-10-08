<?php

namespace App\Controller\Api\Admin;

use App\Entity\Ic\IcCollege;
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
 * CRUD for the colleges shared by the Programs and Scholarships apps (ic_colleges).
 * Prefix /api/admin/ (routes.yaml); global admins only.
 */
class IcCollegeController extends AbstractController
{
    public function __construct(
        private IcService $service,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/colleges', methods: ['GET'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function listAction(Request $request): Response
    {
        [$page, $limit] = RequestHelper::pagination($request, 50);
        $searchTerm = $request->query->get('searchterm');

        $result = $this->service->getCollegesPagination($page, $limit, $searchTerm);
        return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Every college (id + name) for the department form's college picker.
     */
    #[Route('/colleges/dropdown', methods: ['GET'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function dropdownAction(): Response
    {
        return new Response(json_encode($this->service->getCollegesForDropdown()), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/colleges/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function getAction(int $id): Response
    {
        $college = $this->service->getCollegeWithCounts($id);
        if ($college === null) {
            return new Response(json_encode("College not found."), 404, ["Content-Type" => "application/json"]);
        }
        return new Response(json_encode($college), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/colleges', methods: ['POST'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function createAction(Request $request): Response
    {
        $college = new IcCollege();
        $this->applyFields($college, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($college);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($college);
        return new Response($this->serialize($college), 201, ["Content-Type" => "application/json"]);
    }

    #[Route('/colleges/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function updateAction(int $id, Request $request): Response
    {
        $college = $this->service->getCollege($id);
        if ($college === null) {
            return new Response(json_encode("College not found."), 404, ["Content-Type" => "application/json"]);
        }

        $this->applyFields($college, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($college);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($college);
        return new Response($this->serialize($college), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Refuses (409) while any department, program or scholarship still points at the college.
     */
    #[Route('/colleges/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function deleteAction(int $id): Response
    {
        $college = $this->service->getCollege($id);
        if ($college === null) {
            return new Response(json_encode("College not found."), 404, ["Content-Type" => "application/json"]);
        }

        $blocker = $this->service->collegeDeleteBlocker($id);
        if ($blocker !== null) {
            return $this->inUse($blocker);
        }

        try {
            $this->service->delete($college);
        } catch (ForeignKeyConstraintViolationException) {
            return $this->inUse(sprintf("%s can't be deleted because it is still in use.", $college->getCollege()));
        }

        return new Response(null, 204);
    }

    /* ***************************** Helpers ***************************** */

    private function applyFields(IcCollege $college, array $data): void
    {
        if (array_key_exists('college', $data)) {
            $college->setCollege(trim((string) $data['college']));
        }
        if (array_key_exists('url', $data)) {
            $college->setUrl(RequestHelper::optionalString($data['url']));
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
