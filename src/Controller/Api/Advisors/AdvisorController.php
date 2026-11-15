<?php

namespace App\Controller\Api\Advisors;

use App\Entity\Ic\IcAdvisor;
use App\Entity\Ic\IcAdvisorType;
use App\Service\IcService;
use App\Util\RequestHelper;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * CRUD for the advisors shared across IC Command apps (ic_advisors).
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
        $advisor = new IcAdvisor();
        $this->applyFields($advisor, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($advisor);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($advisor);
        return new Response($this->serialize($advisor), 201, ["Content-Type" => "application/json"]);
    }

    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function updateAction(int $id, Request $request): Response
    {
        $advisor = $this->service->getAdvisor($id);
        if ($advisor === null) {
            return new Response(json_encode("Advisor not found."), 404, ["Content-Type" => "application/json"]);
        }

        $this->applyFields($advisor, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($advisor);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($advisor);
        return new Response($this->serialize($advisor), 200, ["Content-Type" => "application/json"]);
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
