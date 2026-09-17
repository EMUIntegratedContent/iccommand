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
        'transfer' => 'setTransfer',
        'housing' => 'setHousing',
        'appProc' => 'setAppProc',
        'amount' => 'setAmount',
        'contact' => 'setContact',
        'overview' => 'setOverview',
    ];

    /**
     * Boolean fields (payload key => entity setter). Applied when the key is present.
     */
    private const BOOL_FIELDS = [
        'active' => 'setActive',
        'isFafsa' => 'setIsFafsa',
        'isParent' => 'setIsParent',
        'isBilingual' => 'setIsBilingual',
        'catchAll' => 'setCatchAll',
    ];

    /**
     * Nullable integer fields (payload key => entity setter). Applied when the key is present.
     */
    private const INT_FIELDS = [
        'collegeId' => 'setCollegeId',
        'departmentId' => 'setDepartmentId',
        'contactId' => 'setContactId',
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

    #[Route('/colleges', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getCollegesAction(): Response
    {
        $colleges = $this->service->getAvailableColleges();
        return new Response(json_encode($colleges), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/departments', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getDepartmentsAction(): Response
    {
        $departments = $this->service->getAvailableDepartments();
        return new Response(json_encode($departments), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/keyword-options', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getKeywordOptionsAction(): Response
    {
        $keywords = $this->service->getAvailableKeywords();
        return new Response(json_encode($keywords), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/organization-options', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getOrganizationOptionsAction(): Response
    {
        $organizations = $this->service->getAvailableOrganizations();
        return new Response(json_encode($organizations), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/options', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN") or is_granted("ROLE_SCHOLARSHIP_VIEW")'))]
    public function getOptionsAction(): Response
    {
        $options = $this->service->getScholarshipOptions();
        return new Response(json_encode($options), 200, ["Content-Type" => "application/json"]);
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

        if (($invalid = $this->invalidProgramIds($data)) !== null) {
            return $invalid;
        }
        if (($invalid = $this->invalidKeywordIds($data)) !== null) {
            return $invalid;
        }
        if (($invalid = $this->invalidOrganizationIds($data)) !== null) {
            return $invalid;
        }
        $this->service->syncProgramLinks($scholarship, $data['program_links'] ?? []);
        $this->service->syncKeywordLinks($scholarship, $data['keyword_ids'] ?? []);
        $this->service->syncOrganizationLinks($scholarship, $data['organization_ids'] ?? []);

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
            if (($invalid = $this->invalidProgramIds($data)) !== null) {
                return $invalid;
            }
            $this->service->syncProgramLinks($scholarship, $data['program_links'] ?? []);
        }
        if (array_key_exists('keyword_ids', $data)) {
            if (($invalid = $this->invalidKeywordIds($data)) !== null) {
                return $invalid;
            }
            $this->service->syncKeywordLinks($scholarship, $data['keyword_ids'] ?? []);
        }
        if (array_key_exists('organization_ids', $data)) {
            if (($invalid = $this->invalidOrganizationIds($data)) !== null) {
                return $invalid;
            }
            $this->service->syncOrganizationLinks($scholarship, $data['organization_ids'] ?? []);
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

        // A cleared field arrives as an empty string. Store null so "no restriction" is
        // consistent in the database and the public feed.
        foreach (self::STRING_FIELDS as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                $scholarship->$setter($val === null || $val === '' ? null : (string)$val);
            }
        }

        foreach (self::BOOL_FIELDS as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $scholarship->$setter((bool)$data[$key]);
            }
        }

        if (array_key_exists('gpa', $data)) {
            $val = $data['gpa'];
            $scholarship->setGpa($val === null || $val === '' ? null : (string)$val);
        }

        foreach (self::INT_FIELDS as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                $scholarship->$setter($val === null || $val === '' ? null : (int)$val);
            }
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
        if (!array_key_exists('program_links', $data)) {
            return null;
        }
        $links = $data['program_links'];
        // A malformed payload (e.g. a string instead of a list) must 422, not fatal on
        // the typed sync method.
        if (!is_array($links)) {
            return $this->invalidIdsResponse('invalid_program_ids', []);
        }
        if (count($links) === 0) {
            return null;
        }
        foreach ($links as $link) {
            if (!is_array($link)) {
                return $this->invalidIdsResponse('invalid_program_ids', []);
            }
        }
        $programIds = array_map(static fn($l) => (int)($l['program_id'] ?? 0), $links);
        $invalid = $this->service->validateProgramIds($programIds);
        if (count($invalid) > 0) {
            return $this->invalidIdsResponse('invalid_program_ids', $invalid);
        }
        return null;
    }

    /**
     * Validates the keyword ids in a payload. Returns a 422 Response if any are invalid,
     * otherwise null.
     */
    private function invalidKeywordIds(array $data): ?Response
    {
        return $this->invalidScalarIds($data, 'keyword_ids', 'invalid_keyword_ids', [$this->service, 'validateKeywordIds']);
    }

    /**
     * Validates the organization ids in a payload. Returns a 422 Response if any are invalid,
     * otherwise null.
     */
    private function invalidOrganizationIds(array $data): ?Response
    {
        return $this->invalidScalarIds($data, 'organization_ids', 'invalid_organization_ids', [$this->service, 'validateOrganizationIds']);
    }

    /**
     * Shared validation for the flat id-array payload keys: the key may be absent or an
     * empty list, but a present value must be a list of scalar ids, and every id must
     * exist (checked by $validator). Returns a 422 Response on failure, otherwise null.
     */
    private function invalidScalarIds(array $data, string $key, string $error, callable $validator): ?Response
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }
        $ids = $data[$key];
        if (!is_array($ids)) {
            return $this->invalidIdsResponse($error, []);
        }
        if (count($ids) === 0) {
            return null;
        }
        foreach ($ids as $id) {
            if (!is_scalar($id)) {
                return $this->invalidIdsResponse($error, []);
            }
        }
        $invalid = $validator(array_map('intval', $ids));
        if (count($invalid) > 0) {
            return $this->invalidIdsResponse($error, $invalid);
        }
        return null;
    }

    private function invalidIdsResponse(string $error, array $ids): Response
    {
        return new Response(
            json_encode(['error' => $error, 'ids' => array_values($ids)]),
            422,
            ["Content-Type" => "application/json"]
        );
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
