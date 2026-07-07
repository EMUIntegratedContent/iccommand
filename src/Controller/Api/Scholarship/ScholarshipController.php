<?php

namespace App\Controller\Api\Scholarship;

use App\Entity\Scholarship\Scholarship;
use App\Service\ScholarshipService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class ScholarshipController extends AbstractController
{
    /**
     * Nullable string fields (payload key => entity setter). Applied when the key is present.
     */
    private const STRING_FIELDS = [
        'url' => 'setUrl',
        'description' => 'setDescription',
        'county' => 'setCounty',
        'city' => 'setCity',
        'state' => 'setState',
        'highSchool' => 'setHighSchool',
        'standingClass' => 'setStandingClass',
        'enrollment' => 'setEnrollment',
        'gender' => 'setGender',
        'ethnicity' => 'setEthnicity',
        'fafsa' => 'setFafsa',
        'isParent' => 'setIsParent',
        'isBilingual' => 'setIsBilingual',
        'organizations' => 'setOrganizations',
        'keywords' => 'setKeywords',
        'housing' => 'setHousing',
        'appProc' => 'setAppProc',
        'amount' => 'setAmount',
        'contact' => 'setContact',
    ];

    private ScholarshipService $service;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    public function __construct(ScholarshipService $service, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->service = $service;
        $this->doctrine = $doctrine;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    #[Route('/list', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getScholarshipsAction(Request $request): Response
    {
        $page = (int)($request->query->get('page') ?? 1);
        $pageSize = (int)($request->query->get('limit') ?? 20);

        $result = $this->service->getScholarshipsPagination($page, $pageSize);

        return $this->json200($result);
    }

    #[Route('/search', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function searchScholarshipsAction(Request $request): Response
    {
        $searchTerm = $request->query->get('searchterm');
        if (!$searchTerm) {
            return new Response(json_encode([]), 200, ["Content-Type" => "application/json"]);
        }

        $scholarships = $this->service->getScholarshipsByName($searchTerm);

        return $this->json200($scholarships);
    }

    #[Route('/programs', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getProgramsAction(): Response
    {
        $programs = $this->service->getAvailablePrograms();
        return new Response(json_encode($programs), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getScholarshipAction(int $id): Response
    {
        $scholarship = $this->doctrine->getRepository(Scholarship::class)->find($id);
        if (!$scholarship) {
            return new Response(json_encode("Scholarship not found."), 404, ["Content-Type" => "application/json"]);
        }

        return $this->json200($scholarship);
    }

    #[Route('/', methods: ['POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_CREATE")'))]
    public function postScholarshipAction(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $scholarship = new Scholarship();
        $this->applyFields($scholarship, $data);

        $errors = $this->service->validate($scholarship);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $invalid = $this->invalidProgramIds($data);
        if ($invalid !== null) {
            return $invalid;
        }
        $this->service->syncProgramLinks($scholarship, $data['program_links'] ?? []);

        $this->em->persist($scholarship);
        $this->em->flush();

        return new Response($this->serialize($scholarship), 201, ["Content-Type" => "application/json"]);
    }

    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_EDIT")'))]
    public function putScholarshipAction(int $id, Request $request): Response
    {
        $scholarship = $this->doctrine->getRepository(Scholarship::class)->find($id);
        if (!$scholarship) {
            return new Response(json_encode("Scholarship not found."), 404, ["Content-Type" => "application/json"]);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $this->applyFields($scholarship, $data);

        $errors = $this->service->validate($scholarship);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        // Only touch links when the payload includes them (supports partial updates).
        if (array_key_exists('program_links', $data)) {
            $invalid = $this->invalidProgramIds($data);
            if ($invalid !== null) {
                return $invalid;
            }
            $this->service->syncProgramLinks($scholarship, $data['program_links'] ?? []);
        }

        $this->em->flush();

        return new Response($this->serialize($scholarship), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_DELETE")'))]
    public function deleteScholarshipAction(int $id): Response
    {
        $scholarship = $this->doctrine->getRepository(Scholarship::class)->find($id);
        if (!$scholarship) {
            return new Response(json_encode("Scholarship not found."), 404, ["Content-Type" => "application/json"]);
        }

        $this->em->remove($scholarship);
        $this->em->flush();

        return new Response(null, 204);
    }

    /* ***************************** Helpers ***************************** */

    /**
     * Applies the provided JSON payload to a Scholarship. Only keys present in the payload
     * are touched, so this serves both create and (partial) update.
     */
    private function applyFields(Scholarship $scholarship, array $data): void
    {
        if (array_key_exists('title', $data)) {
            $scholarship->setTitle((string)$data['title']);
        }

        foreach (self::STRING_FIELDS as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                $scholarship->$setter($val === null ? null : (string)$val);
            }
        }

        if (array_key_exists('active', $data)) {
            $scholarship->setActive((bool)$data['active']);
        }

        if (array_key_exists('gpa', $data)) {
            $val = $data['gpa'];
            $scholarship->setGpa($val === null || $val === '' ? null : (string)$val);
        }

        if (array_key_exists('contactId', $data)) {
            $val = $data['contactId'];
            $scholarship->setContactId($val === null || $val === '' ? null : (int)$val);
        }

        foreach (['applyDate' => 'setApplyDate', 'expDate' => 'setExpDate'] as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                $scholarship->$setter($val === null || $val === '' ? null : new \DateTime($val));
            }
        }
    }

    /**
     * Validates the program ids in a payload against program_programs. Returns a 422 Response
     * if any are invalid, otherwise null.
     */
    private function invalidProgramIds(array $data): ?Response
    {
        $links = $data['program_links'] ?? [];
        if (!is_array($links) || count($links) === 0) {
            return null;
        }
        $programIds = array_map(static fn($l) => (int)($l['program_id'] ?? 0), $links);
        $invalid = $this->service->validateProgramIds($programIds);
        if (count($invalid) > 0) {
            return new Response(
                json_encode(['error' => 'invalid_program_ids', 'ids' => array_values($invalid)]),
                422,
                ["Content-Type" => "application/json"]
            );
        }
        return null;
    }

    private function serialize($data): string
    {
        return $this->serializer->serialize($data, "json", ['groups' => 'scholarship']);
    }

    private function json200($data): Response
    {
        return new Response($this->serialize($data), 200, ["Content-Type" => "application/json"]);
    }
}
