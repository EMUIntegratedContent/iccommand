<?php
namespace App\Repository\Ic;

use App\Entity\Ic\IcCollege;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the colleges shared across IC Command apps.
 * @method IcCollege|null find($id, $lockMode = null, $lockVersion = null)
 * @method IcCollege|null findOneBy(array $criteria, array $orderBy = null)
 * @method IcCollege[]    findAll()
 * @method IcCollege[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IcCollegeRepository extends ServiceEntityRepository
{
    /** The row select shared by the list and single-record queries; counts are correlated subqueries. */
    private const SELECT_WITH_COUNTS = "
        SELECT c.id, c.college, c.url,
            (SELECT COUNT(*) FROM ic_departments d WHERE d.college_id = c.id) AS department_count,
            (SELECT COUNT(DISTINCT l.program_id) FROM program_college_link l WHERE l.college_id = c.id) AS program_count,
            (SELECT COUNT(*) FROM scholarships_scholarship s WHERE s.schlrshp_college_id = c.id) AS scholarship_count
        FROM ic_colleges c";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcCollege::class);
    }

    /**
     * All colleges as plain rows for the college pickers, sorted by name.
     *
     * @return array<int, array{id: int, college: string, url: ?string}>
     */
    public function findForDropdown(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS id', 'c.college AS college', 'c.url AS url')
            ->orderBy('c.college', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Paginated college list for the admin screen, with how many departments, programs
     * (program_college_link) and scholarships point at each college.
     *
     * @return array{colleges: array<int, array{id:int, college:string, url:?string, department_count:int, program_count:int, scholarship_count:int}>, totalRows: int}
     */
    public function paginatedWithCounts(int $page, int $limit, ?string $searchTerm = null): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $where = '';
        $params = [];
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $where = 'WHERE c.college LIKE :term';
            $params['term'] = '%' . trim($searchTerm) . '%';
        }

        $conn = $this->getEntityManager()->getConnection();

        // offset/limit are ints, so inlining them is injection-safe.
        $rows = $conn->executeQuery(
            self::SELECT_WITH_COUNTS . " $where ORDER BY c.college ASC LIMIT $offset, $limit",
            $params
        )->fetchAllAssociative();

        $total = $conn->executeQuery("SELECT COUNT(*) FROM ic_colleges c $where", $params)->fetchOne();

        return [
            'colleges' => array_map([$this, 'castRow'], $rows),
            'totalRows' => (int) $total,
        ];
    }

    /**
     * One college with its usage counts (same shape as a paginatedWithCounts() row).
     */
    public function findOneWithCounts(int $id): ?array
    {
        $row = $this->getEntityManager()->getConnection()
            ->executeQuery(self::SELECT_WITH_COUNTS . ' WHERE c.id = :id', ['id' => $id])
            ->fetchAssociative();

        return $row === false ? null : $this->castRow($row);
    }

    private function castRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'college' => $row['college'],
            'url' => $row['url'],
            'department_count' => (int) $row['department_count'],
            'program_count' => (int) $row['program_count'],
            'scholarship_count' => (int) $row['scholarship_count'],
        ];
    }
}
