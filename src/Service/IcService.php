<?php
namespace App\Service;

use App\Entity\Ic\IcCollege;
use App\Entity\Ic\IcAdvisor;
use App\Entity\Ic\IcDepartment;
use App\Entity\Map\MapBuilding;
use App\Entity\Scholarship\Scholarship;
use App\Repository\Ic\IcCollegeRepository;
use App\Repository\Ic\IcAdvisorRepository;
use App\Repository\Ic\IcDepartmentRepository;
use App\Repository\Map\MapBuildingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Business logic for the colleges, departments and advisors shared across IC Command apps
 * (ic_colleges / ic_departments in the admin area, ic_advisors in the Advisors app).
 */
class IcService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private IcCollegeRepository $colleges,
        private IcDepartmentRepository $departments,
        private IcAdvisorRepository $advisors,
        private MapBuildingRepository $buildings,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * What the current user may do in the Advisors app, for the Vue components. The role
     * hierarchy already gives each role the ones below it; global admins get everything.
     *
     * programLinks / scholarshipLinks say where the usage lists can link: the record's edit
     * page, its read-only page, or nowhere (an advisor editor may have no access to those apps).
     *
     * @return array{admin: bool, delete: bool, edit: bool, view: bool, programLinks: ?string, scholarshipLinks: ?string}
     */
    public function getAdvisorPermissions(): array
    {
        $isGlobalAdmin = $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN');
        $can = fn (string $role): bool => $isGlobalAdmin || $this->authorizationChecker->isGranted($role);
        $linkTo = fn (string $module): ?string => match (true) {
            $can("ROLE_{$module}_EDIT") => 'edit',
            $can("ROLE_{$module}_VIEW") => 'show',
            default => null,
        };

        return [
            'admin' => $can('ROLE_ADVISORS_ADMIN'),
            'delete' => $can('ROLE_ADVISORS_DELETE'),
            'edit' => $can('ROLE_ADVISORS_EDIT'),
            'view' => $can('ROLE_ADVISORS_VIEW'),
            'programLinks' => $linkTo('PROGRAMS'),
            'scholarshipLinks' => $linkTo('SCHOLARSHIP'),
        ];
    }

    public function validate(object $entity): ConstraintViolationListInterface
    {
        return $this->validator->validate($entity);
    }

    public function save(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    public function delete(object $entity): void
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /* ****************************** Colleges ******************************* */

    public function getCollegesPagination(int $page, int $limit, ?string $searchTerm = null): array
    {
        return $this->colleges->paginatedWithCounts($page, $limit, $searchTerm);
    }

    public function getCollegeWithCounts(int $id): ?array
    {
        return $this->colleges->findOneWithCounts($id);
    }

    /**
     * The college with its counts plus the departments, programs and scholarships that use it.
     */
    public function getCollegeWithUsage(int $id): ?array
    {
        $college = $this->getCollegeWithCounts($id);
        return $college === null ? null : $college + $this->colleges->findUsage($id);
    }

    public function getCollege(int $id): ?IcCollege
    {
        return $this->colleges->find($id);
    }

    public function getCollegesForDropdown(): array
    {
        return $this->colleges->findForDropdown();
    }

    /**
     * Why a college can't be deleted (it still has departments, programs or scholarships),
     * or null if nothing uses it.
     */
    public function collegeDeleteBlocker(int $id): ?string
    {
        $college = $this->getCollegeWithCounts($id);
        if ($college === null) {
            return null;
        }

        return $this->blockerMessage($college['college'], [
            'department' => $college['department_count'],
            'program' => $college['program_count'],
            'scholarship' => $college['scholarship_count'],
        ]);
    }

    /* ***************************** Departments ***************************** */

    public function getDepartmentsPagination(int $page, int $limit, ?string $searchTerm = null): array
    {
        return $this->departments->paginatedWithCounts($page, $limit, $searchTerm);
    }

    public function getDepartmentWithCounts(int $id): ?array
    {
        return $this->departments->findOneWithCounts($id);
    }

    /**
     * The department with its counts plus the programs and scholarships that use it.
     */
    public function getDepartmentWithUsage(int $id): ?array
    {
        $department = $this->getDepartmentWithCounts($id);
        return $department === null ? null : $department + $this->departments->findUsage($id);
    }

    public function getDepartment(int $id): ?IcDepartment
    {
        return $this->departments->find($id);
    }

    public function getDepartmentsForDropdown(): array
    {
        return $this->departments->findForDropdown();
    }

    /**
     * Why a department can't be deleted (programs or scholarships still use it), or null if
     * nothing uses it.
     */
    public function departmentDeleteBlocker(int $id): ?string
    {
        $department = $this->getDepartmentWithCounts($id);
        if ($department === null) {
            return null;
        }

        return $this->blockerMessage($department['department'], [
            'program' => $department['program_count'],
            'scholarship' => $department['scholarship_count'],
        ]);
    }

    /* ****************************** Advisors ******************************* */

    public function getAdvisorsPagination(int $page, int $limit, ?string $searchTerm = null): array
    {
        return $this->advisors->paginatedWithCounts($page, $limit, $searchTerm);
    }

    public function getAdvisorWithCounts(int $id): ?array
    {
        return $this->advisors->findOneWithCounts($id);
    }

    /**
     * The advisor with its counts plus the records that use it.
     */
    public function getAdvisorWithUsage(int $id): ?array
    {
        $advisor = $this->getAdvisorWithCounts($id);
        return $advisor === null ? null : $advisor + $this->advisors->findUsage($id);
    }

    public function getAdvisor(int $id): ?IcAdvisor
    {
        return $this->advisors->find($id);
    }

    public function getAdvisorsForDropdown(): array
    {
        return $this->advisors->findForDropdown();
    }

    /**
     * The advisors a form picked, without duplicates. Cleared pickers (null or '') are skipped,
     * so an empty list means no advisors.
     *
     * @param mixed $ids list of advisor ids; anything else counts as an empty list
     * @return IcAdvisor[]
     * @throws UnprocessableEntityHttpException for an id that doesn't exist, e.g. an advisor
     *                                          deleted while the form was open
     */
    public function resolveAdvisors(mixed $ids): array
    {
        $message = 'That advisor no longer exists.';
        $wanted = $this->parseIds($ids, $message);
        if ($wanted === []) {
            return [];
        }

        $found = [];
        foreach ($this->advisors->findBy(['id' => $wanted]) as $advisor) {
            $found[$advisor->getId()] = $advisor;
        }
        if (count($found) !== count($wanted)) {
            throw new UnprocessableEntityHttpException($message);
        }

        return array_map(fn (int $id) => $found[$id], $wanted);
    }

    /**
     * Makes these a program's or scholarship's advisors, in a transaction. For program_advisor,
     * which has no entity; scholarships save theirs through Scholarship::$icAdvisors.
     *
     * @param string $linkTable IcAdvisorRepository::PROGRAM_LINK or SCHOLARSHIP_LINK
     * @param IcAdvisor[] $advisors from resolveAdvisors()
     */
    public function setRecordAdvisors(string $linkTable, int $recordId, array $advisors): void
    {
        $advisorIds = array_map(fn (IcAdvisor $advisor) => $advisor->getId(), $advisors);
        $this->em->getConnection()->transactional(
            fn () => $this->advisors->replaceRecordAdvisors($linkTable, $recordId, $advisorIds)
        );
    }

    /**
     * Parses a form's list of ids: a non-list counts as an empty list, null and '' entries
     * (pickers with no choice) are skipped, and duplicates are dropped. An entry that isn't an
     * integer id, including true/false, throws.
     *
     * @return int[] in first-seen order
     * @throws UnprocessableEntityHttpException with $message
     */
    private function parseIds(mixed $ids, string $message): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $parsed = [];
        foreach ($ids as $id) {
            if ($id === null || $id === '') {
                continue;
            }
            $int = is_bool($id) ? false : filter_var($id, FILTER_VALIDATE_INT);
            if ($int === false) {
                throw new UnprocessableEntityHttpException($message);
            }
            $parsed[$int] = $int;
        }

        return array_values($parsed);
    }

    /**
     * Every program or scholarship, for the advisor form's "add" search.
     *
     * @param string $linkTable IcAdvisorRepository::PROGRAM_LINK or SCHOLARSHIP_LINK
     */
    public function getRecordOptions(string $linkTable): array
    {
        return $this->advisors->findRecordOptions($linkTable);
    }

    /**
     * The programs or scholarships an advisor form picked, without duplicates. Like
     * resolveAdvisors(): a non-list counts as an empty list, and null or '' entries are skipped.
     *
     * @param string $linkTable IcAdvisorRepository::PROGRAM_LINK or SCHOLARSHIP_LINK
     * @return int[]
     * @throws UnprocessableEntityHttpException for an id that doesn't exist, e.g. a record
     *                                          deleted while the form was open
     */
    public function resolveRecordIds(string $linkTable, mixed $ids): array
    {
        $noun = $linkTable === IcAdvisorRepository::PROGRAM_LINK ? 'program' : 'scholarship';
        $message = "That $noun no longer exists.";
        $wanted = $this->parseIds($ids, $message);

        if (count($this->advisors->findExistingRecordIds($linkTable, $wanted)) !== count($wanted)) {
            throw new UnprocessableEntityHttpException($message);
        }

        return $wanted;
    }

    /**
     * Saves the advisor and, in the same transaction, makes each given list its programs or
     * scholarships. A link table missing from $links keeps its rows, and so does one whose list
     * didn't change.
     *
     * Gedmo only sees column changes, so a save that changes links touches the advisor's
     * updated/updatedBy itself, and those of the scholarships gained or lost (programs have no
     * audit fields).
     *
     * @param array<string, int[]> $links link table => record ids (from resolveRecordIds())
     */
    public function saveAdvisor(IcAdvisor $advisor, array $links): void
    {
        $this->em->wrapInTransaction(function () use ($advisor, $links): void {
            $changedScholarshipIds = [];
            foreach ($links as $linkTable => $recordIds) {
                $current = $advisor->getId() === null ? [] : $this->advisors->findRecordIdsForAdvisor($linkTable, $advisor->getId());
                $gained = array_diff($recordIds, $current);
                $lost = array_diff($current, $recordIds);
                if ($gained === [] && $lost === []) {
                    unset($links[$linkTable]);
                    continue;
                }
                if ($advisor->getId() !== null) {
                    $advisor->touch();
                }
                if ($linkTable === IcAdvisorRepository::SCHOLARSHIP_LINK) {
                    $changedScholarshipIds = array_merge($gained, $lost);
                }
            }

            $this->em->persist($advisor);
            $this->em->flush(); // A new advisor needs its id before it can be linked.
            foreach ($links as $linkTable => $recordIds) {
                $this->advisors->replaceAdvisorLinks($linkTable, $advisor->getId(), $recordIds);
            }

            // Flushed when the transaction commits.
            if ($changedScholarshipIds !== []) {
                foreach ($this->em->getRepository(Scholarship::class)->findBy(['id' => $changedScholarshipIds]) as $scholarship) {
                    $scholarship->touch();
                }
            }
        });
    }

    /**
     * The buildings an advisor can be in, for the advisor form's picker.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getBuildingOptions(): array
    {
        return $this->buildings->findOptions();
    }

    /**
     * The building an advisor form picked: null for none (null or '').
     *
     * @throws UnprocessableEntityHttpException for an id that isn't a building, e.g. one
     *                                          deleted while the form was open
     */
    public function resolveBuilding(mixed $id): ?MapBuilding
    {
        $message = 'That building no longer exists.';
        $ids = $this->parseIds([$id], $message);
        if ($ids === []) {
            return null;
        }

        return $this->buildings->find($ids[0]) ?? throw new UnprocessableEntityHttpException($message);
    }

    /**
     * What the public feeds publish about each advisor, as a list per record id, for one of
     * the advisor link tables (see IcAdvisorRepository::LINK_TABLES). Records without
     * advisors are absent. Only the given records' links and advisors are loaded (all records
     * when null).
     *
     * @param int[]|null $recordIds
     * @return array<int, array<int, array{name: string, email: ?string, phone: ?string, url: ?string, buildingName: ?string}>>
     */
    public function getIcAdvisorsInfoByRecord(string $linkTable, ?array $recordIds = null): array
    {
        $idsByRecord = $this->advisors->findAdvisorIdsByRecord($linkTable, $recordIds);
        $info = $this->advisors->findPublicInfoById(array_values(array_unique(array_merge([], ...array_values($idsByRecord)))));

        $byRecord = [];
        foreach ($idsByRecord as $recordId => $advisorIds) {
            $byRecord[$recordId] = array_values(array_filter(array_map(fn (int $id) => $info[$id] ?? null, $advisorIds)));
        }

        return $byRecord;
    }

    /**
     * Adds each row's icAdvisorsInfo: the public details of its program's advisors, or [].
     * The program ids come separately because a row's own id may be cut short (the search
     * feed shortens ids to 5 digits for catalog links).
     *
     * @param array<int, array> $rows program rows
     * @param int[] $programIds each row's full program id, in row order
     */
    public function addProgramAdvisorsInfo(array $rows, array $programIds): array
    {
        $info = $this->getIcAdvisorsInfoByRecord(IcAdvisorRepository::PROGRAM_LINK, $programIds);

        foreach (array_values($rows) as $index => $row) {
            $row['icAdvisorsInfo'] = $info[$programIds[$index]] ?? [];
            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * Why an advisor can't be deleted (records still use it), or null if nothing uses it.
     */
    public function advisorDeleteBlocker(int $id): ?string
    {
        $advisor = $this->getAdvisorWithCounts($id);
        if ($advisor === null) {
            return null;
        }

        return $this->blockerMessage($advisor['display_name'], [
            'program' => $advisor['program_count'],
            'scholarship' => $advisor['scholarship_count'],
        ]);
    }

    /**
     * Builds e.g. "College of Business can't be deleted: it is used by 4 departments,
     * 62 programs and 44 scholarships." from the non-zero counts, or null if all are zero.
     *
     * @param array<string, int> $counts singular noun => count
     */
    private function blockerMessage(string $name, array $counts): ?string
    {
        $parts = [];
        foreach ($counts as $noun => $count) {
            if ($count > 0) {
                $parts[] = $count . ' ' . $noun . ($count === 1 ? '' : 's');
            }
        }
        if ($parts === []) {
            return null;
        }

        $last = array_pop($parts);
        $list = $parts === [] ? $last : implode(', ', $parts) . ' and ' . $last;

        return sprintf("%s can't be deleted: it is used by %s.", $name, $list);
    }
}
