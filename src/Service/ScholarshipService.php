<?php
namespace App\Service;

use App\Entity\Programs\Programs;
use App\Entity\Scholarship\Scholarship;
use App\Entity\Scholarship\ScholarshipKeyword;
use App\Entity\Scholarship\ScholarshipKeywordLink;
use App\Entity\Scholarship\ScholarshipOrganization;
use App\Entity\Scholarship\ScholarshipOrganizationLink;
use App\Entity\Scholarship\ScholarshipProgram;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
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
     * The public criteria search. Pass includeExpired=1 to keep scholarships whose deadline
     * has already passed.
     */
    public function searchPublicScholarships(array $params): array
    {
        $includeExpired = filter_var($params['includeExpired'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $repository = $this->doctrine->getRepository(Scholarship::class);
        return $repository->searchPublicScholarships($params, $includeExpired);
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
            // $this->em is the default EM, which holds the Programs metadata now that
            // program_programs lives in the ic database. getReference avoids a DB hit;
            // the ids were already existence-checked by validateProgramIds upstream.
            $scholarship->addProgramLink(
                (new ScholarshipProgram())
                    ->setProgram($this->em->getReference(Programs::class, $programId))
                    ->setNotes($notes)
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

    /* ****************************** Keywords ******************************* */

    /**
     * Paginated keyword list with per-keyword scholarship counts.
     */
    public function getKeywordsPagination(int $page, int $limit, ?string $searchTerm = null): array
    {
        return $this->em->getRepository(ScholarshipKeyword::class)
            ->paginatedWithScholarshipCount($page, $limit, $searchTerm);
    }

    /**
     * All keywords (id + keyword) for the form picker.
     */
    public function getAvailableKeywords(): array
    {
        return $this->em->createQuery(
            'SELECT k.id, k.keyword FROM App\Entity\Scholarship\ScholarshipKeyword k ORDER BY k.keyword ASC'
        )->getArrayResult();
    }

    public function getKeyword(int $id): ?ScholarshipKeyword
    {
        return $this->em->find(ScholarshipKeyword::class, $id);
    }

    public function findKeywordByNameCI(string $name): ?ScholarshipKeyword
    {
        return $this->em->getRepository(ScholarshipKeyword::class)->findOneByNameCI($name);
    }

    public function createKeyword(string $name): ScholarshipKeyword
    {
        $keyword = (new ScholarshipKeyword())->setKeyword(trim($name));
        $this->em->persist($keyword);
        $this->em->flush();
        return $keyword;
    }

    public function deleteKeyword(int $id): void
    {
        $keyword = $this->getKeyword($id);
        if ($keyword !== null) {
            // orphanRemoval + FK ON DELETE CASCADE remove the link rows automatically.
            $this->em->remove($keyword);
            $this->em->flush();
        }
    }

    /**
     * Scholarships (id + title) linked to a keyword.
     */
    public function getScholarshipsForKeyword(int $keywordId): array
    {
        return $this->em->createQuery(
            'SELECT s.id, s.title FROM App\Entity\Scholarship\Scholarship s
             JOIN s.keywordLinks kl WHERE IDENTITY(kl.keyword) = :id ORDER BY s.title ASC'
        )->setParameter('id', $keywordId)->getArrayResult();
    }

    public function linkScholarshipToKeyword(int $keywordId, int $scholarshipId): void
    {
        $existing = $this->em->find(ScholarshipKeywordLink::class, ['scholarship' => $scholarshipId, 'keyword' => $keywordId]);
        if ($existing !== null) {
            return;
        }
        $link = (new ScholarshipKeywordLink())
            ->setScholarship($this->em->getReference(Scholarship::class, $scholarshipId))
            ->setKeyword($this->em->getReference(ScholarshipKeyword::class, $keywordId));
        $this->em->persist($link);
        $this->em->flush();
    }

    public function unlinkScholarshipFromKeyword(int $keywordId, int $scholarshipId): void
    {
        $link = $this->em->find(ScholarshipKeywordLink::class, ['scholarship' => $scholarshipId, 'keyword' => $keywordId]);
        if ($link !== null) {
            $this->em->remove($link);
            $this->em->flush();
        }
    }

    /**
     * Resyncs a scholarship's keyword links from a flat list of ids (set semantics). The
     * caller flushes. Ids must have been validated by validateKeywordIds() upstream.
     *
     * @param int[] $ids
     */
    public function syncKeywordLinks(Scholarship $scholarship, array $ids): void
    {
        $desired = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));

        $seen = [];
        foreach ($scholarship->getKeywordLinks() as $existing) {
            $keywordId = $existing->getKeyword()->getId();
            if (in_array($keywordId, $desired, true)) {
                $seen[$keywordId] = true;
            } else {
                $scholarship->removeKeywordLink($existing);
            }
        }

        foreach ($desired as $keywordId) {
            if (!isset($seen[$keywordId])) {
                $scholarship->addKeywordLink(
                    (new ScholarshipKeywordLink())->setKeyword($this->em->getReference(ScholarshipKeyword::class, $keywordId))
                );
            }
        }
    }

    /**
     * @param int[] $ids
     * @return int[] Ids that do not exist.
     */
    public function validateKeywordIds(array $ids): array
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids)), fn($i) => $i > 0));
        if (count($ids) === 0) {
            return [];
        }
        $found = array_map('intval', array_column($this->em->createQuery(
            'SELECT k.id FROM App\Entity\Scholarship\ScholarshipKeyword k WHERE k.id IN (:ids)'
        )->setParameter('ids', $ids)->getScalarResult(), 'id'));
        return array_values(array_diff($ids, $found));
    }

    /**
     * Bulk create keywords from parsed CSV rows (['keyword' => string, 'scholarship_id' => ?int]).
     * Case-insensitive + in-batch dedupe; optionally links to a scholarship.
     *
     * @param array<int, array{keyword?: string, scholarship_id?: mixed}> $rows
     * @return array{created:int, skipped:int, rejected:int, linkSkipped:int}
     */
    public function bulkCreateKeywords(array $rows, ?DebugDataHolder $debug = null): array
    {
        $created = $skipped = $rejected = $linkSkipped = 0;
        $seenInCsv = [];
        $i = 0;

        foreach ($rows as $row) {
            $name = trim((string)($row['keyword'] ?? ''));
            if ($name === '') {
                $rejected++;
                continue;
            }
            $key = mb_strtolower($name);

            $keyword = $seenInCsv[$key] ?? $this->findKeywordByNameCI($name);
            if ($keyword !== null) {
                $skipped++;
            } else {
                try {
                    $keyword = $this->createKeyword($name);
                    $created++;
                } catch (UniqueConstraintViolationException) {
                    $keyword = $this->findKeywordByNameCI($name);
                    $skipped++;
                }
            }
            if ($keyword !== null) {
                $seenInCsv[$key] = $keyword;
            }

            $scholarshipId = (int)($row['scholarship_id'] ?? 0);
            if ($scholarshipId > 0 && $keyword !== null) {
                if ($this->em->find(Scholarship::class, $scholarshipId) !== null) {
                    $this->linkScholarshipToKeyword($keyword->getId(), $scholarshipId);
                } else {
                    $linkSkipped++;
                }
            }

            if (++$i % 50 === 0) {
                $seenInCsv = [];
                $debug?->reset();
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'rejected' => $rejected, 'linkSkipped' => $linkSkipped];
    }

    /* **************************** Organizations **************************** */

    public function getOrganizationsPagination(int $page, int $limit, ?string $searchTerm = null): array
    {
        return $this->em->getRepository(ScholarshipOrganization::class)
            ->paginatedWithScholarshipCount($page, $limit, $searchTerm);
    }

    public function getAvailableOrganizations(): array
    {
        return $this->em->createQuery(
            'SELECT o.id, o.organization FROM App\Entity\Scholarship\ScholarshipOrganization o ORDER BY o.organization ASC'
        )->getArrayResult();
    }

    public function getOrganization(int $id): ?ScholarshipOrganization
    {
        return $this->em->find(ScholarshipOrganization::class, $id);
    }

    public function findOrganizationByNameCI(string $name): ?ScholarshipOrganization
    {
        return $this->em->getRepository(ScholarshipOrganization::class)->findOneByNameCI($name);
    }

    public function createOrganization(string $name): ScholarshipOrganization
    {
        $organization = (new ScholarshipOrganization())->setOrganization(trim($name));
        $this->em->persist($organization);
        $this->em->flush();
        return $organization;
    }

    public function deleteOrganization(int $id): void
    {
        $organization = $this->getOrganization($id);
        if ($organization !== null) {
            $this->em->remove($organization);
            $this->em->flush();
        }
    }

    public function getScholarshipsForOrganization(int $organizationId): array
    {
        return $this->em->createQuery(
            'SELECT s.id, s.title FROM App\Entity\Scholarship\Scholarship s
             JOIN s.organizationLinks ol WHERE IDENTITY(ol.organization) = :id ORDER BY s.title ASC'
        )->setParameter('id', $organizationId)->getArrayResult();
    }

    public function linkScholarshipToOrganization(int $organizationId, int $scholarshipId): void
    {
        $existing = $this->em->find(ScholarshipOrganizationLink::class, ['scholarship' => $scholarshipId, 'organization' => $organizationId]);
        if ($existing !== null) {
            return;
        }
        $link = (new ScholarshipOrganizationLink())
            ->setScholarship($this->em->getReference(Scholarship::class, $scholarshipId))
            ->setOrganization($this->em->getReference(ScholarshipOrganization::class, $organizationId));
        $this->em->persist($link);
        $this->em->flush();
    }

    public function unlinkScholarshipFromOrganization(int $organizationId, int $scholarshipId): void
    {
        $link = $this->em->find(ScholarshipOrganizationLink::class, ['scholarship' => $scholarshipId, 'organization' => $organizationId]);
        if ($link !== null) {
            $this->em->remove($link);
            $this->em->flush();
        }
    }

    /**
     * @param int[] $ids
     */
    public function syncOrganizationLinks(Scholarship $scholarship, array $ids): void
    {
        $desired = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));

        $seen = [];
        foreach ($scholarship->getOrganizationLinks() as $existing) {
            $organizationId = $existing->getOrganization()->getId();
            if (in_array($organizationId, $desired, true)) {
                $seen[$organizationId] = true;
            } else {
                $scholarship->removeOrganizationLink($existing);
            }
        }

        foreach ($desired as $organizationId) {
            if (!isset($seen[$organizationId])) {
                $scholarship->addOrganizationLink(
                    (new ScholarshipOrganizationLink())->setOrganization($this->em->getReference(ScholarshipOrganization::class, $organizationId))
                );
            }
        }
    }

    /**
     * @param int[] $ids
     * @return int[] Ids that do not exist.
     */
    public function validateOrganizationIds(array $ids): array
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids)), fn($i) => $i > 0));
        if (count($ids) === 0) {
            return [];
        }
        $found = array_map('intval', array_column($this->em->createQuery(
            'SELECT o.id FROM App\Entity\Scholarship\ScholarshipOrganization o WHERE o.id IN (:ids)'
        )->setParameter('ids', $ids)->getScalarResult(), 'id'));
        return array_values(array_diff($ids, $found));
    }

    /**
     * @param array<int, array{organization?: string, scholarship_id?: mixed}> $rows
     * @return array{created:int, skipped:int, rejected:int, linkSkipped:int}
     */
    public function bulkCreateOrganizations(array $rows, ?DebugDataHolder $debug = null): array
    {
        $created = $skipped = $rejected = $linkSkipped = 0;
        $seenInCsv = [];
        $i = 0;

        foreach ($rows as $row) {
            $name = trim((string)($row['organization'] ?? ''));
            if ($name === '') {
                $rejected++;
                continue;
            }
            $key = mb_strtolower($name);

            $organization = $seenInCsv[$key] ?? $this->findOrganizationByNameCI($name);
            if ($organization !== null) {
                $skipped++;
            } else {
                try {
                    $organization = $this->createOrganization($name);
                    $created++;
                } catch (UniqueConstraintViolationException) {
                    $organization = $this->findOrganizationByNameCI($name);
                    $skipped++;
                }
            }
            if ($organization !== null) {
                $seenInCsv[$key] = $organization;
            }

            $scholarshipId = (int)($row['scholarship_id'] ?? 0);
            if ($scholarshipId > 0 && $organization !== null) {
                if ($this->em->find(Scholarship::class, $scholarshipId) !== null) {
                    $this->linkScholarshipToOrganization($organization->getId(), $scholarshipId);
                } else {
                    $linkSkipped++;
                }
            }

            if (++$i % 50 === 0) {
                $seenInCsv = [];
                $debug?->reset();
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'rejected' => $rejected, 'linkSkipped' => $linkSkipped];
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
