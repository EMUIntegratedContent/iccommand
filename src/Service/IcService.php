<?php
namespace App\Service;

use App\Entity\Ic\IcCollege;
use App\Entity\Ic\IcDepartment;
use App\Repository\Ic\IcCollegeRepository;
use App\Repository\Ic\IcDepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Business logic for the colleges and departments shared across IC Command apps
 * (ic_colleges / ic_departments), managed from the admin area.
 */
class IcService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private IcCollegeRepository $colleges,
        private IcDepartmentRepository $departments,
    ) {
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

    public function getDepartment(int $id): ?IcDepartment
    {
        return $this->departments->find($id);
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
