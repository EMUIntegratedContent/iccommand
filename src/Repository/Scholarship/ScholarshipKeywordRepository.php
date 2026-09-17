<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\ScholarshipKeyword;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the managed scholarship keywords.
 * @method ScholarshipKeyword|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScholarshipKeyword|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScholarshipKeyword[]    findAll()
 * @method ScholarshipKeyword[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipKeywordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScholarshipKeyword::class);
    }

    /**
     * Paginated keyword list with the count of scholarships linked to each.
     *
     * @return array{keywords: array<int, array{id:int, keyword:string, scholarship_count:int}>, totalRows: int}
     */
    public function paginatedWithScholarshipCount(int $page, int $limit, ?string $searchTerm = null): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createQueryBuilder('k')
            ->select('k.id AS id', 'k.keyword AS keyword', 'COUNT(kl.keyword) AS scholarship_count')
            ->leftJoin('k.scholarshipLinks', 'kl')
            ->groupBy('k.id')
            ->addGroupBy('k.keyword')
            ->orderBy('k.keyword', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $countQb = $this->createQueryBuilder('k')->select('COUNT(DISTINCT k.id)');

        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $qb->andWhere('k.keyword LIKE :term')->setParameter('term', '%' . trim($searchTerm) . '%');
            $countQb->andWhere('k.keyword LIKE :term')->setParameter('term', '%' . trim($searchTerm) . '%');
        }

        $rows = array_map(
            static fn (array $r) => [
                'id' => (int) $r['id'],
                'keyword' => $r['keyword'],
                'scholarship_count' => (int) $r['scholarship_count'],
            ],
            $qb->getQuery()->getArrayResult()
        );

        return [
            'keywords' => $rows,
            'totalRows' => (int) $countQb->getQuery()->getSingleScalarResult(),
        ];
    }

    /**
     * Case-insensitive lookup by name, for dedupe on create / CSV import.
     */
    public function findOneByNameCI(string $keyword): ?ScholarshipKeyword
    {
        return $this->createQueryBuilder('k')
            ->where('LOWER(k.keyword) = LOWER(:keyword)')
            ->setParameter('keyword', trim($keyword))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
