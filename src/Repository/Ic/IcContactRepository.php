<?php
namespace App\Repository\Ic;

use App\Entity\Ic\IcContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the contacts shared across IC Command apps.
 * @method IcContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method IcContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method IcContact[]    findAll()
 * @method IcContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IcContactRepository extends ServiceEntityRepository
{
    /** IcContact::getDisplayName() in SQL, for a contact aliased as c. */
    public const DISPLAY_NAME_SQL = "IF(c.type = 'Person', CONCAT(c.last_name, ', ', c.first_name), c.organization)";

    /**
     * The tables joining a record to its contacts: (record id column, contact_id). Both are
     * foreign keys; the contact side has no ON DELETE action, so a used contact can't be deleted.
     */
    public const PROGRAM_LINK = 'program_contact';
    public const SCHOLARSHIP_LINK = 'scholarship_contact';
    public const BUILDING_LINK = 'map_building_contact';

    /** link table => its record id column */
    public const LINK_TABLES = [
        self::PROGRAM_LINK => 'program_id',
        self::SCHOLARSHIP_LINK => 'scholarship_id',
        self::BUILDING_LINK => 'building_id',
    ];

    /** The row select shared by the list and single-record queries; counts are correlated subqueries. */
    private const SELECT_WITH_COUNTS = "
        SELECT c.*, " . self::DISPLAY_NAME_SQL . " AS display_name,
            (SELECT COUNT(*) FROM program_contact x WHERE x.contact_id = c.id) AS program_count,
            (SELECT COUNT(*) FROM scholarship_contact x WHERE x.contact_id = c.id) AS scholarship_count,
            (SELECT COUNT(*) FROM map_building_contact x WHERE x.contact_id = c.id) AS building_count
        FROM ic_contacts c";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcContact::class);
    }

    /**
     * All contacts as plain rows for the contact pickers, sorted by name.
     *
     * @return array<int, array{id: int, displayName: string, type: string, email: ?string}>
     */
    public function findForDropdown(): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT c.id, ' . self::DISPLAY_NAME_SQL . ' AS displayName, c.type, c.email
             FROM ic_contacts c ORDER BY displayName ASC'
        )->fetchAllAssociative();

        return array_map(fn (array $row) => ['id' => (int) $row['id']] + $row, $rows);
    }

    /**
     * What the public feeds publish about each contact, keyed by contact id.
     *
     * @return array<int, array{name: string, email: ?string, phone: ?string, url: ?string}>
     */
    public function findPublicInfoById(): array
    {
        $info = [];
        foreach ($this->findAll() as $contact) {
            $info[$contact->getId()] = $contact->toPublicArray();
        }

        return $info;
    }

    /**
     * The contact ids of each record in a link table, keyed by record id, each list sorted by
     * contact name.
     *
     * @param string $linkTable one of the LINK_TABLES keys
     * @return array<int, int[]>
     */
    public function findContactIdsByRecord(string $linkTable): array
    {
        $recordColumn = self::LINK_TABLES[$linkTable] ?? throw new \InvalidArgumentException("Unknown contact link table $linkTable.");

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT l.$recordColumn AS record_id, l.contact_id
             FROM $linkTable l JOIN ic_contacts c ON c.id = l.contact_id
             ORDER BY " . self::DISPLAY_NAME_SQL . ' ASC, c.id ASC'
        )->fetchAllAssociative();

        $byRecord = [];
        foreach ($rows as $row) {
            $byRecord[(int) $row['record_id']][] = (int) $row['contact_id'];
        }

        return $byRecord;
    }

    /**
     * Paginated contact list for the admin screen, with how many records point at each
     * contact. The search term matches the name, organization or email.
     *
     * @return array{contacts: array<int, array>, totalRows: int}
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

        $total = $conn->executeQuery("SELECT COUNT(*) FROM ic_contacts c $where", $params)->fetchOne();

        return [
            'contacts' => array_map([$this, 'castRow'], $rows),
            'totalRows' => (int) $total,
        ];
    }

    /**
     * One contact with its usage counts (same shape as a paginatedWithCounts() row).
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
        $queries = [
            'programs' => 'SELECT x.id, x.full_name, x.catalog FROM program_programs x
                JOIN program_contact l ON l.program_id = x.id
                WHERE l.contact_id = :id ORDER BY x.full_name ASC, x.catalog ASC',
            'scholarships' => 'SELECT x.id, x.schlrshp_title AS title FROM scholarships_scholarship x
                JOIN scholarship_contact l ON l.scholarship_id = x.id
                WHERE l.contact_id = :id ORDER BY title ASC',
            'buildings' => 'SELECT x.id, i.name FROM map_building x
                JOIN map_item i ON i.id = x.id
                JOIN map_building_contact l ON l.building_id = x.id
                WHERE l.contact_id = :id ORDER BY i.name ASC',
        ];

        $conn = $this->getEntityManager()->getConnection();
        $usage = [];
        foreach ($queries as $key => $sql) {
            $rows = $conn->executeQuery($sql, ['id' => $id])->fetchAllAssociative();
            $usage[$key] = array_map(fn (array $row) => ['id' => (int) $row['id']] + $row, $rows);
        }

        return $usage;
    }

    private function castRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        foreach (['program', 'scholarship', 'building'] as $noun) {
            $row[$noun . '_count'] = (int) $row[$noun . '_count'];
        }

        return $row;
    }
}
