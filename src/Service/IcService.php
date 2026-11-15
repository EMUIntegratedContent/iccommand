<?php
namespace App\Service;

use App\Entity\Ic\IcCollege;
use App\Entity\Ic\IcAdvisor;
use App\Entity\Ic\IcDepartment;
use App\Repository\Ic\IcCollegeRepository;
use App\Repository\Ic\IcAdvisorRepository;
use App\Repository\Ic\IcDepartmentRepository;
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
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * What the current user may do in the Advisors app, for the Vue components. Each role
     * inherits the ones below it; global admins get everything.
     *
     * @return array{admin: bool, edit: bool, delete: bool, view: bool}
     */
    public function getAdvisorPermissions(): array
    {
        $permissions = ['admin' => false, 'edit' => false, 'delete' => false, 'view' => false];

        if ($this->authorizationChecker->isGranted('ROLE_ADVISORS_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
            $permissions['admin'] = true;
            $permissions['delete'] = true;
            $permissions['edit'] = true;
            $permissions['view'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_ADVISORS_DELETE')) {
            $permissions['delete'] = true;
            $permissions['edit'] = true;
            $permissions['view'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_ADVISORS_EDIT')) {
            $permissions['edit'] = true;
            $permissions['view'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_ADVISORS_VIEW')) {
            $permissions['view'] = true;
        }

        return $permissions;
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
        if (!is_array($ids)) {
            return [];
        }

        $advisors = [];
        foreach ($ids as $id) {
            if ($id === null || $id === '') {
                continue;
            }

            $advisorId = filter_var($id, FILTER_VALIDATE_INT);
            $advisor = $advisorId === false ? null : $this->advisors->find($advisorId);
            if ($advisor === null) {
                throw new UnprocessableEntityHttpException('That advisor no longer exists.');
            }
            $advisors[$advisor->getId()] = $advisor;
        }

        return array_values($advisors);
    }

    /**
     * What the public feeds publish about each advisor, as a list per record id, for one of
     * the advisor link tables (see IcAdvisorRepository::LINK_TABLES). Records without
     * advisors are absent.
     *
     * @return array<int, array<int, array{name: string, email: ?string, phone: ?string, url: ?string}>>
     */
    public function getIcAdvisorsInfoByRecord(string $linkTable): array
    {
        $info = $this->advisors->findPublicInfoById();

        $byRecord = [];
        foreach ($this->advisors->findAdvisorIdsByRecord($linkTable) as $recordId => $advisorIds) {
            $byRecord[$recordId] = array_values(array_filter(array_map(fn (int $id) => $info[$id] ?? null, $advisorIds)));
        }

        return $byRecord;
    }

    /**
     * Adds each row's icAdvisorsInfo: the public details of its program's advisors, or [].
     *
     * @param array<int, array> $rows program rows with an id key
     */
    public function addProgramAdvisorsInfo(array $rows): array
    {
        $info = $this->getIcAdvisorsInfoByRecord(IcAdvisorRepository::PROGRAM_LINK);

        return array_map(function (array $row) use ($info) {
            $row['icAdvisorsInfo'] = $info[(int) $row['id']] ?? [];
            return $row;
        }, $rows);
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
            'building' => $advisor['building_count'],
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
