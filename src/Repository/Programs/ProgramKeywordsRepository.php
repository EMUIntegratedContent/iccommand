<?php

namespace App\Repository\Programs;

use App\Entity\Programs\ProgramKeywords;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<ProgramKeywords>
 */
class ProgramKeywordsRepository extends ServiceEntityRepository
{
    protected ObjectManager $em;

    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, ProgramKeywords::class);
        $this->em = $doctrine->getManager('programs');
    }

    /**
     * Delete all links for a keyword by keyword ID.
     * @param int $keywordId
     * @return void
     */
    public function deleteByKeywordId(int $keywordId): void
    {
        $conn = $this->em->getConnection();
        $sql = 'DELETE FROM programs.program_keyword_links WHERE keyword_id = :keyword_id';
        $conn->executeStatement($sql, ['keyword_id' => $keywordId]);
    }

    public function getKeywordEntity(int $keywordId): ?ProgramKeywords
    {
        return $this->em->find(ProgramKeywords::class, $keywordId);
    }

    /**
     * Get all programs linked to a keyword.
     * @param int $keywordId
     * @return array
     */
    public function getProgramsForKeyword(int $keywordId): array
    {
        $sql = "
            SELECT p.id, p.program, p.full_name, p.catalog
            FROM programs.program_programs p
            INNER JOIN programs.program_keyword_links pkl ON p.id = pkl.program_id
            WHERE pkl.keyword_id = :keyword_id
            ORDER BY p.full_name ASC
        ";

        return $this->em->getConnection()->executeQuery($sql, ['keyword_id' => $keywordId])->fetchAllAssociative();
    }

    /**
     * Link a program to a keyword.
     * @param int $keywordId
     * @param int $programId
     * @return void
     */
    public function linkProgramToKeyword(int $keywordId, int $programId): void
    {
        $conn = $this->em->getConnection();

        // Check if link already exists
        $checkSql = 'SELECT COUNT(*) FROM programs.program_keyword_links WHERE keyword_id = :keyword_id AND program_id = :program_id';
        $count = $conn->executeQuery(
            $checkSql,
            [
                'keyword_id' => $keywordId,
                'program_id' => $programId
            ]
        )->fetchOne();

        if ($count == 0) {
            $sql = 'INSERT INTO programs.program_keyword_links (keyword_id, program_id) VALUES (:keyword_id, :program_id)';
            $conn->executeStatement($sql, [
                'keyword_id' => $keywordId,
                'program_id' => $programId
            ]);
        }
    }

    /**
     * Unlink a program from a keyword.
     * @param int $keywordId
     * @param int $programId
     * @return void
     */
    public function unlinkProgramFromKeyword(int $keywordId, int $programId): void
    {
        $conn = $this->em->getConnection();
        $sql = 'DELETE FROM programs.program_keyword_links WHERE keyword_id = :keyword_id AND program_id = :program_id';
        $conn->executeStatement($sql, [
            'keyword_id' => $keywordId,
            'program_id' => $programId
        ]);
    }

    /**
     * Find all with program count, with pagination and optional search
     * @param int $page
     * @param int $limit
     * @param string|null $searchTerm
     * @return array
     */
    public function findAllWithProgramCountPagination(int $page, int $limit, ?string $searchTerm = null): array
    {
        $conn = $this->em->getConnection();
        $offset = ($page - 1) * $limit;

        // Build WHERE clause for search
        $whereClause = '';
        $params = [];
        if ($searchTerm !== null && $searchTerm !== '') {
            $whereClause = 'WHERE pk.keyword LIKE :searchTerm';
            $params['searchTerm'] = '%' . $searchTerm . '%';
        }

        // Get paginated results (using string interpolation for LIMIT/OFFSET like ProgramsRepository)
        $sql = "
            SELECT pk.id, pk.keyword, COUNT(pkl.program_id) AS program_count
            FROM programs.program_keywords pk
            LEFT JOIN programs.program_keyword_links pkl ON pk.id = pkl.keyword_id
            {$whereClause}
            GROUP BY pk.id, pk.keyword
            ORDER BY pk.keyword ASC
            LIMIT {$offset}, {$limit}
        ";
        $results = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        // Get total count
        $countSql = "
            SELECT COUNT(DISTINCT pk.id) AS total
            FROM programs.program_keywords pk
            {$whereClause}
        ";
        $totalCount = $conn->executeQuery($countSql, $params)->fetchOne();

        return [
            'keywords' => $results,
            'totalRows' => (int)$totalCount
        ];
    }
}
