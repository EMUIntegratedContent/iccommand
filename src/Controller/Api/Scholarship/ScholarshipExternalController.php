<?php

namespace App\Controller\Api\Scholarship;

use App\Entity\Scholarship\Scholarship;
use App\Service\ScholarshipService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Public (unauthenticated) read endpoints for scholarships. Access is granted by the
 * `^/api/external` = PUBLIC_ACCESS rule in security.yaml — no #[IsGranted] here.
 * Only active scholarships are exposed.
 */
class ScholarshipExternalController extends AbstractController
{
    /**
     * Serialization context for the public endpoints.
     * Uses the scholarship group, minus the Gedmo audit fields and contactId.
     * Excluded here instead of on the entity so the admin API keeps them.
     */
    private const PUBLIC_CONTEXT = [
        'groups' => 'scholarship',
        AbstractNormalizer::IGNORED_ATTRIBUTES => ['created', 'createdBy', 'updated', 'updatedBy', 'contactId'],
    ];

    private ManagerRegistry $doctrine;
    private NormalizerInterface $normalizer;
    private ScholarshipService $service;

    public function __construct(ManagerRegistry $doctrine, NormalizerInterface $normalizer, ScholarshipService $service)
    {
        $this->doctrine = $doctrine;
        $this->normalizer = $normalizer;
        $this->service = $service;
    }

    #[Route('/all', methods: ['GET'])]
    public function getScholarshipsAction(): Response
    {
        // No criteria, so this returns everything currently on offer.
        $scholarships = $this->service->searchPublicScholarships([]);

        return new Response(json_encode($this->publicRows($scholarships)), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Criteria search for the CMS pages. Declared before /{id} so it isn't read as an id.
     */
    #[Route('/search', methods: ['GET'])]
    public function searchScholarshipsAction(Request $request): Response
    {
        $scholarships = $this->service->searchPublicScholarships($request->query->all());

        return new Response(json_encode($this->publicRows($scholarships)), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getScholarshipAction(int $id): Response
    {
        $scholarship = $this->doctrine->getRepository(Scholarship::class)
            ->findOneBy(['id' => $id, 'active' => true]);

        // Expired scholarships are hidden from the feed, so they 404 here too.
        if (!$scholarship || $this->hasExpired($scholarship)) {
            return new Response(json_encode("Scholarship not found."), 404, ["Content-Type" => "application/json"]);
        }

        return new Response(json_encode($this->publicRows([$scholarship])[0]), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Normalizes scholarships with PUBLIC_CONTEXT, swapping collegeId/departmentId for the
     * college and department names (in place, so the field order is unchanged).
     *
     * @param Scholarship[] $scholarships
     */
    private function publicRows(array $scholarships): array
    {
        $colleges = array_column($this->service->getAvailableColleges(), 'college', 'id');
        $departments = array_column($this->service->getAvailableDepartments(), 'department', 'id');

        $rows = [];
        foreach ($this->normalizer->normalize(array_values($scholarships), "json", self::PUBLIC_CONTEXT) as $row) {
            $named = [];
            foreach ($row as $key => $value) {
                if ($key === 'collegeId') {
                    $named['college'] = $value === null ? null : ($colleges[$value] ?? null);
                } elseif ($key === 'departmentId') {
                    $named['department'] = $value === null ? null : ($departments[$value] ?? null);
                } else {
                    $named[$key] = $value;
                }
            }
            $rows[] = $named;
        }

        return $rows;
    }

    private function hasExpired(Scholarship $scholarship): bool
    {
        $expDate = $scholarship->getExpDate();

        return $expDate !== null && $expDate < new \DateTime('today');
    }
}
