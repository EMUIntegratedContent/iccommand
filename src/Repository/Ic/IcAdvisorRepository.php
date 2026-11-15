<?php
namespace App\Repository\Ic;

use App\Entity\Ic\IcAdvisor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the advisors shared across IC Command apps.
 * @method IcAdvisor|null find($id, $lockMode = null, $lockVersion = null)
 * @method IcAdvisor|null findOneBy(array $criteria, array $orderBy = null)
 * @method IcAdvisor[]    findAll()
 * @method IcAdvisor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IcAdvisorRepository extends ServiceEntityRepository
{
    /** IcAdvisor::getDisplayName() in SQL, for an advisor aliased as c. */
    public const DISPLAY_NAME_SQL = "IF(c.type = 'Person', CONCAT(c.last_name, ', ', c.first_name), c.organization)";

    /**
     * The tables joining a record to its advisors: (record id column, advisor_id). Both are
     * foreign keys; the advisor side has no ON DELETE action, so a used advisor can't be deleted.
     */
    public const PROGRAM_LINK = 'program_advisor';
    public const SCHOLARSHIP_LINK = 'scholarship_advisor';

    /** link table => its record id column */
    public const LINK_TABLES = [
        self::PROGRAM_LINK => 'program_id',
        self::SCHOLARSHIP_LINK => 'scholarship_id',
    ];

    /** The usage list (and option list) key of each link table's records. */
    public const RECORD_KINDS = [
        'programs' => self::PROGRAM_LINK,
        'scholarships' => self::SCHOLARSHIP_LINK,
    ];

    /**
     * link table => [record table, the row columns (record aliased as x), order]. Usage rows
     * and option rows share these columns, so a picked option can join the usage list as is.
     */
    private const RECORDS = [
        self::PROGRAM_LINK => ['program_programs', 'x.id, x.full_name, x.catalog, x.is_active AS active', 'x.full_name ASC, x.catalog ASC, x.id ASC'],
        self::SCHOLARSHIP_LINK => ['scholarships_scholarship', 'x.id, x.schlrshp_title AS title, x.schlrshp_active AS active', 'x.schlrshp_title ASC, x.id ASC'],
    ];

    /**
     * The row select shared by the list and single-record queries: the advisor, its building's
     * name, and how many records use it (correlated subqueries).
     */
    private const SELECT_WITH_COUNTS = "
        SELECT c.*, " . self::DISPLAY_NAME_SQL . " AS display_name, b.name AS building_name,
            (SELECT COUNT(*) FROM program_advisor x WHERE x.advisor_id = c.id) AS program_count,
            (SELECT COUNT(*) FROM scholarship_advisor x WHERE x.advisor_id = c.id) AS scholarship_count
        FROM ic_advisors c
        LEFT JOIN map_item b ON b.id = c.building_id";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcAdvisor::class);
    }

    /**
     * All advisors as plain rows for the advisor pickers, sorted by name.
     *
     * @return array<int, array{id: int, displayName: string, type: string, email: ?string}>
     */
    public function findForDropdown(): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT c.id, ' . self::DISPLAY_NAME_SQL . ' AS displayName, c.type, c.email
             FROM ic_advisors c ORDER BY displayName ASC'
        )->fetchAllAssociative();

        return array_map(fn (array $row) => ['id' => (int) $row['id']] + $row, $rows);
    }

    /**
     * What the public feeds publish about each advisor, keyed by advisor id.
     *
     * @return array<int, array{name: string, email: ?string, phone: ?string, url: ?string, buildingName: ?string}>
     */
    public function findPublicInfoById(): array
    {
        // Fetch-joins the buildings, since toPublicArray() reads each one's name.
        $advisors = $this->createQueryBuilder('a')
            ->leftJoin('a.building', 'b')->addSelect('b')
            ->getQuery()->getResult();

        $info = [];
        foreach ($advisors as $advisor) {
            $info[$advisor->getId()] = $advisor->toPublicArray();
        }

        return $info;
    }

    /**
     * The advisor ids of each record in a link table, keyed by record id, each list sorted by
     * advisor name.
     *
     * @param string $linkTable one of the LINK_TABLES keys
     * @return array<int, int[]>
     */
    public function findAdvisorIdsByRecord(string $linkTable): array
    {
        $recordColumn = self::LINK_TABLES[$linkTable] ?? throw new \InvalidArgumentException("Unknown advisor link table $linkTable.");

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT l.$recordColumn AS record_id, l.advisor_id
             FROM $linkTable l JOIN ic_advisors c ON c.id = l.advisor_id
             ORDER BY " . self::DISPLAY_NAME_SQL . ' ASC, c.id ASC'
        )->fetchAllAssociative();

        $byRecord = [];
        foreach ($rows as $row) {
            $byRecord[(int) $row['record_id']][] = (int) $row['advisor_id'];
        }

        return $byRecord;
    }

    /**
     * Paginated advisor list for the admin screen, with how many records point at each
     * advisor. The search term matches the name, organization or email.
     *
     * @return array{advisors: array<int, array>, totalRows: int}
     */
    public function paginatedWithCounts(int $page, int $limit, ?string $searchTerm = null): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $where = '';
        $params = [];
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $where = "WHERE (c.organization LIKE :term OR c.email LIKE :term
                OR CONCAT(c.first_name, ' ', c.last_name) LIKE :term
                OR CONCAT(c.last_name, ', ', c.first_name) LIKE :term)";
            $params['term'] = '%' . trim($searchTerm) . '%';
        }

        $conn = $this->getEntityManager()->getConnection();

        // offset/limit are ints, so inlining them is injection-safe.
        $rows = $conn->executeQuery(
            self::SELECT_WITH_COUNTS . " $where ORDER BY display_name ASC LIMIT $offset, $limit",
            $params
        )->fetchAllAssociative();

        $total = $conn->executeQuery("SELECT COUNT(*) FROM ic_advisors c $where", $params)->fetchOne();

        return [
            'advisors' => array_map([$this, 'castRow'], $rows),
            'totalRows' => (int) $total,
        ];
    }

    /**
     * One advisor with its usage counts (same shape as a paginatedWithCounts() row).
     */
    public function findOneWithCounts(int $id): ?array
    {
        $row = $this->getEntityManager()->getConnection()
            ->executeQuery(self::SELECT_WITH_COUNTS . ' WHERE c.id = :id', ['id' => $id])
            ->fetchAssociative();

        return $row === false ? null : $this->castRow($row);
    }

    /**
     * The records behind findOneWithCounts()'s counts, sorted by name, for the edit page's
     * usage lists.
     *
     * @return array<string, array<int, array>>
     */
    public function findUsage(int $id): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $usage = [];
        foreach (self::RECORD_KINDS as $key => $linkTable) {
            [$table, $columns, $order] = self::RECORDS[$linkTable];
            $recordColumn = self::LINK_TABLES[$linkTable];
            $rows = $conn->executeQuery(
                "SELECT $columns FROM $table x JOIN $linkTable l ON l.$recordColumn = x.id
                 WHERE l.advisor_id = :id ORDER BY $order",
                ['id' => $id]
            )->fetchAllAssociative();
            $usage[$key] = array_map([$this, 'castRecordRow'], $rows);
        }

        return $usage;
    }

    /**
     * Every program or scholarship, for the advisor form's "add" search. Same row shape as
     * findUsage(): programs {id, full_name, catalog, active}, scholarships {id, title, active}.
     *
     * @param string $linkTable one of the LINK_TABLES keys
     */
    public function findRecordOptions(string $linkTable): array
    {
        [$table, $columns, $order] = self::RECORDS[$linkTable] ?? throw new \InvalidArgumentException("Unknown advisor link table $linkTable.");

        $rows = $this->getEntityManager()->getConnection()
            ->executeQuery("SELECT $columns FROM $table x ORDER BY $order")
            ->fetchAllAssociative();

        return array_map([$this, 'castRecordRow'], $rows);
    }

    /**
     * Which of these program or scholarship ids exist.
     *
     * @param int[] $ids
     * @return int[]
     */
    public function findExistingRecordIds(string $linkTable, array $ids): array
    {
        [$table] = self::RECORDS[$linkTable] ?? throw new \InvalidArgumentException("Unknown advisor link table $linkTable.");
        if ($ids === []) {
            return [];
        }

        $found = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT id FROM $table WHERE id IN (?)",
            [array_values($ids)],
            [ArrayParameterType::INTEGER]
        )->fetchFirstColumn();

        return array_map('intval', $found);
    }

    /**
     * Makes these the advisor's programs or scholarships. The ids must exist. Doesn't open a
     * transaction itself: IcService::saveAdvisor() runs it inside the advisor's save.
     *
     * @param int[] $recordIds
     */
    public function replaceAdvisorLinks(string $linkTable, int $advisorId, array $recordIds): void
    {
        $recordColumn = self::LINK_TABLES[$linkTable] ?? throw new \InvalidArgumentException("Unknown advisor link table $linkTable.");
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeStatement("DELETE FROM $linkTable WHERE advisor_id = ?", [$advisorId]);
        foreach (array_unique($recordIds) as $recordId) {
            $conn->executeStatement("INSERT INTO $linkTable ($recordColumn, advisor_id) VALUES (?, ?)", [$recordId, $advisorId]);
        }
    }

    private function castRecordRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['active'] = (bool) $row['active'];

        return $row;
    }

    private function castRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['building_id'] = $row['building_id'] === null ? null : (int) $row['building_id'];
        foreach (['program', 'scholarship'] as $noun) {
            $row[$noun . '_count'] = (int) $row[$noun . '_count'];
        }

        return $row;
    }
}
