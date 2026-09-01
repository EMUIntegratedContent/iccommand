<?php

namespace App\Controller\Api\Scholarship;

use App\Entity\Scholarship\Scholarship;
use App\Service\ScholarshipService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Managed organization CRUD + scholarship-link endpoints. Prefix /api/scholarships/ (routes.yaml).
 * All work is on the default entity manager via ScholarshipService.
 */
class ScholarshipOrganizationController extends AbstractController
{
    private const VIEW = 'is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")';
    private const CREATE = 'is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_CREATE")';
    private const EDIT = 'is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_EDIT")';
    private const DELETE = 'is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_DELETE")';

    public function __construct(
        private ScholarshipService $service,
        private SerializerInterface $serializer,
        private ManagerRegistry $doctrine,
    ) {
    }

    #[Route('/organizations', methods: ['GET'])]
    #[IsGranted(new Expression(self::VIEW))]
    public function listAction(Request $request): Response
    {
        $page = (int)($request->query->get('page') ?? 1);
        $limit = (int)($request->query->get('limit') ?? 50);
        $searchTerm = $request->query->get('searchterm');

        $result = $this->service->getOrganizationsPagination($page, $limit, $searchTerm);
        return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/organizations', methods: ['POST'])]
    #[IsGranted(new Expression(self::CREATE))]
    public function createAction(Request $request): Response
    {
        $name = trim((string)$request->request->get('organization'));
        if ($name === '') {
            return new Response("Organization name is required.", 422, ["Content-Type" => "application/json"]);
        }
        if ($this->service->findOrganizationByNameCI($name) !== null) {
            return new Response("That organization already exists.", 422, ["Content-Type" => "application/json"]);
        }
        try {
            $organization = $this->service->createOrganization($name);
        } catch (UniqueConstraintViolationException) {
            return new Response("That organization already exists.", 422, ["Content-Type" => "application/json"]);
        }
        return new Response(
            $this->serializer->serialize($organization, "json", ['groups' => 'scholarship_organization']),
            201,
            ["Content-Type" => "application/json"]
        );
    }

    #[Route('/organizations/upload', methods: ['POST'])]
    #[IsGranted(new Expression(self::CREATE))]
    public function uploadAction(
        Request $request,
        ?Profiler $profiler = null,
        #[Autowire(service: 'doctrine.debug_data_holder')]
        ?DebugDataHolder $debugDataHolder = null,
    ): Response {
        $profiler?->disable();
        $debugDataHolder?->reset();

        $uploaded = $request->files->get('csv');
        if (!$uploaded) {
            return new Response("No CSV file was uploaded.", 422, ["Content-Type" => "application/json"]);
        }

        $file = file($uploaded);
        $csvFile = array_map('str_getcsv', $file);
        $headers = array_shift($csvFile);

        $rows = [];
        foreach ($csvFile as $row) {
            if (count($row) === 1 && trim((string)$row[0]) === '') {
                continue;
            }
            $row = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
            $rows[] = array_combine($headers, $row);
        }

        $result = $this->service->bulkCreateOrganizations($rows, $debugDataHolder);

        $message = sprintf('%d created.', $result['created']);
        $message .= sprintf('<br>%d skipped (already exists).', $result['skipped']);
        $message .= sprintf('<br>%d rejected (blank organization).', $result['rejected']);
        if ($result['linkSkipped'] > 0) {
            $message .= sprintf('<br>%d created but scholarship link skipped (scholarship not found).', $result['linkSkipped']);
        }

        return new Response($message, 201, ["Content-Type" => "application/json"]);
    }

    #[Route('/organizations/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression(self::DELETE))]
    public function deleteAction(int $id): Response
    {
        $this->service->deleteOrganization($id);
        return new Response(null, 204);
    }

    #[Route('/organizations/{id}/scholarships', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression(self::VIEW))]
    public function scholarshipsAction(int $id): Response
    {
        if ($this->service->getOrganization($id) === null) {
            return new Response("Organization not found.", 404, ["Content-Type" => "application/json"]);
        }
        $scholarships = $this->service->getScholarshipsForOrganization($id);
        return new Response(json_encode($scholarships), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/organizations/{id}/scholarships', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression(self::EDIT))]
    public function linkAction(int $id, Request $request): Response
    {
        if ($this->service->getOrganization($id) === null) {
            return new Response("Organization not found.", 404, ["Content-Type" => "application/json"]);
        }
        $scholarshipId = (int)$request->request->get('scholarship_id');
        if ($scholarshipId <= 0) {
            return new Response("Scholarship ID is required.", 422, ["Content-Type" => "application/json"]);
        }
        if ($this->doctrine->getRepository(Scholarship::class)->find($scholarshipId) === null) {
            return new Response("Scholarship not found.", 404, ["Content-Type" => "application/json"]);
        }
        $this->service->linkScholarshipToOrganization($id, $scholarshipId);
        return new Response("Scholarship linked successfully.", 201, ["Content-Type" => "application/json"]);
    }

    #[Route('/organizations/{id}/scholarships/{scholarshipId}', methods: ['DELETE'], requirements: ['id' => '\d+', 'scholarshipId' => '\d+'])]
    #[IsGranted(new Expression(self::EDIT))]
    public function unlinkAction(int $id, int $scholarshipId): Response
    {
        $this->service->unlinkScholarshipFromOrganization($id, $scholarshipId);
        return new Response(null, 204);
    }
}
