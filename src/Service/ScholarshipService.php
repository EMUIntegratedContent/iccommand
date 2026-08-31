<?php
namespace App\Service;

use App\Entity\Scholarship\Scholarship;
use App\Entity\Scholarship\ScholarshipProgram;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ScholarshipService
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private ValidatorInterface $validator;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $em;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
        $this->doctrine = $doctrine;
        $this->em = $em;
    }

    /**
     * Get the permissions of the user for managing scholarships.
     * @return array The user's permissions.
     */
    public function getScholarshipPermissions(): array
    {
        // Set all permissions to false as default.
        $permissions = array(
            'admin' => false,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'view' => false,
        );

        // The admins automatically have all the permissions.
        if ($this->authorizationChecker->isGranted('ROLE_SCHOLARSHIP_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
            $permissions['admin'] = true;
            $permissions['create'] = true;
            $permissions['edit'] = true;
            $permissions['delete'] = true;
            $permissions['view'] = true;
        }

        if ($this->authorizationChecker->isGranted('ROLE_SCHOLARSHIP_DELETE')) {
            $permissions['create'] = true;
            $permissions['edit'] = true;
            $permissions['delete'] = true;
            $permissions['view'] = true;
        }

        if ($this->authorizationChecker->isGranted('ROLE_SCHOLARSHIP_EDIT')) {
            $permissions['edit'] = true;
            $permissions['view'] = true;
        }

        if ($this->authorizationChecker->isGranted('ROLE_SCHOLARSHIP_CREATE')) {
            $permissions['create'] = true;
            $permissions['view'] = true;
        }

        if ($this->authorizationChecker->isGranted('ROLE_SCHOLARSHIP_VIEW')) {
            $permissions['view'] = true;
        }

        return $permissions;
    }

    public function validate($entity): ConstraintViolationListInterface
    {
        return $this->validator->validate($entity);
    }

    public function getScholarshipsPagination(int $currentPage, int $pageSize, ?bool $activeOnly = null): array
    {
        $repository = $this->doctrine->getRepository(Scholarship::class);
        return $repository->paginatedScholarships($currentPage, $pageSize, $activeOnly);
    }

    public function getScholarshipsByName(string $searchTerm): array
    {
        $repository = $this->doctrine->getRepository(Scholarship::class);
        return $repository->searchScholarships($searchTerm);
    }

    /**
     * Resyncs a scholarship's program links from a payload of {program_id, notes} objects.
     * Removes links no longer present (orphanRemoval deletes the rows), updates notes on
     * surviving links, and adds new ones. The caller is responsible for flushing.
     *
     * @param array $links List of ['program_id' => int, 'notes' => ?string]
     */
    public function syncProgramLinks(Scholarship $scholarship, array $links): void
    {
        // Build desired map programId => notes (last one wins on duplicates).
        $desired = [];
        foreach ($links as $link) {
            $programId = (int)($link['program_id'] ?? 0);
            if ($programId <= 0) {
                continue;
            }
            $desired[$programId] = isset($link['notes']) && $link['notes'] !== '' ? (string)$link['notes'] : null;
        }

        // Update / remove existing links.
        $seen = [];
        foreach ($scholarship->getProgramLinks() as $existing) {
            $programId = $existing->getProgramId();
            if (!array_key_exists($programId, $desired)) {
                $scholarship->removeProgramLink($existing);
                continue;
            }
            $existing->setNotes($desired[$programId]);
            $seen[$programId] = true;
        }

        // Add new links.
        foreach ($desired as $programId => $notes) {
            if (isset($seen[$programId])) {
                continue;
            }
            $scholarship->addProgramLink(
                (new ScholarshipProgram())->setProgramId($programId)->setNotes($notes)
            );
        }
    }

    /**
     * Returns the subset of the given program ids that do NOT exist in program_programs,
     * so a caller can reject an invalid link payload before it hits a DB FK error.
     *
     * @param int[] $ids
     * @return int[] Invalid (non-existent) program ids.
     */
    public function validateProgramIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, fn($id) => $id > 0);
        if (count($ids) === 0) {
            return [];
        }

        $conn = $this->doctrine->getManager('programs')->getConnection();
        $rows = $conn->executeQuery(
            'SELECT id FROM program_programs WHERE id IN (?)',
            [array_values($ids)],
            [\Doctrine\DBAL\ArrayParameterType::INTEGER]
        )->fetchFirstColumn();

        $existing = array_map('intval', $rows);
        return array_values(array_diff($ids, $existing));
    }

    /**
     * All programs (id + full_name) for the link picker.
     */
    public function getAvailablePrograms(): array
    {
        $conn = $this->doctrine->getManager('programs')->getConnection();
        return $conn->executeQuery(
            'SELECT id, full_name FROM program_programs ORDER BY full_name ASC'
        )->fetchAllAssociative();
    }

    /**
     * All colleges (id + college) for the awarding college picker.
     */
    public function getAvailableColleges(): array
    {
        $conn = $this->doctrine->getManager('programs')->getConnection();
        return $conn->executeQuery(
            'SELECT id, college FROM program_colleges ORDER BY college ASC'
        )->fetchAllAssociative();
    }

    /**
     * All departments (id + department) for the awarding department picker.
     */
    public function getAvailableDepartments(): array
    {
        $conn = $this->doctrine->getManager('programs')->getConnection();
        return $conn->executeQuery(
            'SELECT id, department FROM program_departments ORDER BY department ASC'
        )->fetchAllAssociative();
    }

    /**
     * Get the option lists for the constrained scholarship fields.
     * @return array
     */
    public function getScholarshipOptions(): array
    {
        return [
            'gender' => Scholarship::GENDER_OPTIONS,
            'ethnicity' => Scholarship::ETHNICITY_OPTIONS,
            'gpa' => Scholarship::GPA_OPTIONS,
            'classStanding' => Scholarship::CLASS_STANDING_OPTIONS,
            'housing' => Scholarship::HOUSING_OPTIONS,
            'transfer' => Scholarship::TRANSFER_OPTIONS,
            'state' => Scholarship::STATE_OPTIONS,
        ];
    }
}
