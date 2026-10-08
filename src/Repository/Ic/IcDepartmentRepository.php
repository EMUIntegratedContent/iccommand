<?php
namespace App\Repository\Ic;

use App\Entity\Ic\IcDepartment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the departments shared across IC Command apps.
 * @method IcDepartment|null find($id, $lockMode = null, $lockVersion = null)
 * @method IcDepartment|null findOneBy(array $criteria, array $orderBy = null)
 * @method IcDepartment[]    findAll()
 * @method IcDepartment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IcDepartmentRepository extends ServiceEntityRepository
{
    /** The row select shared by the list and single-record queries; counts are correlated subqueries. */
    private const SELECT_WITH_COUNTS = "
        SELECT d.id, d.department, d.college_id, c.college,
            (SELECT COUNT(DISTINCT x.program_id) FROM program_inter_dept x WHERE x.department_id = d.id) AS program_count,
            (SELECT COUNT(*) FROM scholarships_scholarship s WHERE s.schlrshp_department_id = d.id) AS scholarship_count
        FROM ic_departments d
        JOIN ic_colleges c ON c.id = d.college_id";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcDepartment::class);
    }

    /**
     * All departments as plain rows for the department pickers, sorted by name.
     *
     * @return array<int, array{id: int, department: string, college_id: int}>
     */
    public function findForDropdown(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.id AS id', 'd.department AS department', 'IDENTITY(d.college) AS college_id')
            ->orderBy('d.department', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Paginated department list for the admin screen, with each department's college and how
     * many programs (program_inter_dept) and scholarships point at it. The search term
     * matches the department or college name.
     *
     * @return array{departments: array<int, array{id:int, department:string, college_id:int, college:string, program_count:int, scholarship_count:int}>, totalRows: int}
     */
    public function paginatedWithCounts(int $page, int $limit, ?string $searchTerm = null): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $where = '';
        $params = [];
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $where = 'WHERE (d.department LIKE :term OR c.college LIKE :term)';
            $params['term'] = '%' . trim($searchTerm) . '%';
        }

        $conn = $this->getEntityManager()->getConnection();

        // offset/limit are ints, so inlining them is injection-safe.
        $rows = $conn->executeQuery(
            self::SELECT_WITH_COUNTS . " $where ORDER BY d.department ASC LIMIT $offset, $limit",
            $params
        )->fetchAllAssociative();

        $total = $conn->executeQuery(
            "SELECT COUNT(*) FROM ic_departments d JOIN ic_colleges c ON c.id = d.college_id $where",
            $params
        )->fetchOne();

        return [
            'departments' => array_map([$this, 'castRow'], $rows),
            'totalRows' => (int) $total,
        ];
    }

    /**
     * One department with its usage counts (same shape as a paginatedWithCounts() row).
     */
    public function findOneWithCounts(int $id): ?array
    {
        $row = $this->getEntityManager()->getConnection()
            ->executeQuery(self::SELECT_WITH_COUNTS . ' WHERE d.id = :id', ['id' => $id])
            ->fetchAssociative();

        return $row === false ? null : $this->castRow($row);
    }

    private function castRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'department' => $row['department'],
            'college_id' => (int) $row['college_id'],
            'college' => $row['college'],
            'program_count' => (int) $row['program_count'],
            'scholarship_count' => (int) $row['scholarship_count'],
        ];
    }
}
