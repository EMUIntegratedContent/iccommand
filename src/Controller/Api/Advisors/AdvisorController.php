<?php

namespace App\Controller\Api\Advisors;

use App\Entity\Ic\IcAdvisor;
use App\Entity\Ic\IcAdvisorType;
use App\Repository\Ic\IcAdvisorRepository;
use App\Service\IcService;
use App\Util\RequestHelper;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * CRUD for the advisors shared across IC Command apps (ic_advisors), including which programs and
 * scholarships use each one, so advisor editors can link them without access to those apps.
 * Prefix /api/advisors (routes.yaml). View reads, edit writes, delete removes; global admins can do all.
 */
class AdvisorController extends AbstractController
{
    /**
     * Optional text fields (payload key => entity setter). Applied when the key is present.
     */
    private const STRING_FIELDS = [
        'lastName' => 'setLastName',
        'firstName' => 'setFirstName',
        'organization' => 'setOrganization',
        'email' => 'setEmail',
        'phone' => 'setPhone',
        'room' => 'setRoom',
        'area' => 'setArea',
        'url' => 'setUrl',
        'foragerUrl' => 'setForagerUrl',
        'imageUrl' => 'setImageUrl',
    ];

    /**
     * Lists of linked records (payload key => link table). Each replaces the advisor's links of
     * that kind when the key is present, like advisorIds on the records' own save routes.
     */
    private const LINK_FIELDS = [
        'programIds' => IcAdvisorRepository::PROGRAM_LINK,
        'scholarshipIds' => IcAdvisorRepository::SCHOLARSHIP_LINK,
    ];

    public function __construct(
        private IcService $service,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_VIEW")'))]
    public function listAction(Request $request): Response
    {
        [$page, $limit] = RequestHelper::pagination($request, 50);
        $searchTerm = $request->query->get('searchterm');

        $result = $this->service->getAdvisorsPagination($page, $limit, $searchTerm);
        return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * The buildings the form's picker offers (id, name), sorted by name. Only editors pick one;
     * readers see the advisor's building_name.
     */
    #[Route('/buildings', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function buildingOptionsAction(): Response
    {
        return new Response(json_encode($this->service->getBuildingOptions()), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Every program (id, full_name, catalog, active), for the form's "add a program" search.
     */
    #[Route('/programs', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function programOptionsAction(): Response
    {
        return new Response(json_encode($this->service->getRecordOptions(IcAdvisorRepository::PROGRAM_LINK)), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Every scholarship (id, title, active), for the form's "add a scholarship" search.
     */
    #[Route('/scholarships', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function scholarshipOptionsAction(): Response
    {
        return new Response(json_encode($this->service->getRecordOptions(IcAdvisorRepository::SCHOLARSHIP_LINK)), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_VIEW")'))]
    public function getAction(int $id): Response
    {
        $advisor = $this->service->getAdvisorWithUsage($id);
        if ($advisor === null) {
            return new Response(json_encode("Advisor not found."), 404, ["Content-Type" => "application/json"]);
        }
        return new Response(json_encode($advisor), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function createAction(Request $request): Response
    {
        return $this->saveFromRequest(new IcAdvisor(), $request, 201);
    }

    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function updateAction(int $id, Request $request): Response
    {
        $advisor = $this->service->getAdvisor($id);
        if ($advisor === null) {
            return new Response(json_encode("Advisor not found."), 404, ["Content-Type" => "application/json"]);
        }

        return $this->saveFromRequest($advisor, $request, 200);
    }

    /**
     * Refuses (409) while any program or scholarship still points at the advisor.
     */
    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_DELETE")'))]
    public function deleteAction(int $id): Response
    {
        $advisor = $this->service->getAdvisor($id);
        if ($advisor === null) {
            return new Response(json_encode("Advisor not found."), 404, ["Content-Type" => "application/json"]);
        }

        $blocker = $this->service->advisorDeleteBlocker($id);
        if ($blocker !== null) {
            return $this->inUse($blocker);
        }

        try {
            $this->service->delete($advisor);
        } catch (ForeignKeyConstraintViolationException) {
            return $this->inUse(sprintf("%s can't be deleted because it is still in use.", $advisor->getDisplayName()));
        }

        return new Response(null, 204);
    }

    /* ***************************** Helpers ***************************** */

    /**
     * Applies the payload, checks it, and saves the advisor with its program and scholarship
     * links. Nothing is written unless everything is valid.
     */
    private function saveFromRequest(IcAdvisor $advisor, Request $request, int $successStatus): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $this->applyFields($advisor, $data);
            $links = [];
            foreach (self::LINK_FIELDS as $key => $linkTable) {
                if (array_key_exists($key, $data)) {
                    $links[$linkTable] = $this->service->resolveRecordIds($linkTable, $data[$key]);
                }
            }
        } catch (UnprocessableEntityHttpException $e) {
            return $this->unprocessable($e->getMessage());
        }

        $errors = $this->service->validate($advisor);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        try {
            $this->service->saveAdvisor($advisor, $links);
        } catch (ForeignKeyConstraintViolationException) {
            // A building, program or scholarship was deleted between the check and the save.
            return $this->unprocessable('A building, program or scholarship you picked no longer exists.');
        }

        return new Response($this->serialize($advisor), $successStatus, ["Content-Type" => "application/json"]);
    }

    /**
     * A 422 shaped like a validation failure, so the form lists the message.
     */
    private function unprocessable(string $message): Response
    {
        return new Response(json_encode([
            'title' => 'Validation Failed',
            'violations' => [['propertyPath' => '', 'title' => $message]],
        ]), 422, ["Content-Type" => "application/json"]);
    }

    /**
     * An unknown or blank type leaves the type unset so validation reports it. buildingId
     * null or '' clears the building; an id that isn't a building is a 422.
     */
    private function applyFields(IcAdvisor $advisor, array $data): void
    {
        if (array_key_exists('type', $data)) {
            $advisor->setType(is_string($data['type']) ? IcAdvisorType::tryFrom($data['type']) : null);
        }

        if (array_key_exists('buildingId', $data)) {
            $advisor->setBuilding($this->service->resolveBuilding($data['buildingId']));
        }

        foreach (self::STRING_FIELDS as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $advisor->$setter(RequestHelper::optionalString($data[$key]));
            }
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
