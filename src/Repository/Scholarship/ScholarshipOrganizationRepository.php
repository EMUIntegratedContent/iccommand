<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\ScholarshipOrganization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the managed scholarship organizations.
 * @method ScholarshipOrganization|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScholarshipOrganization|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScholarshipOrganization[]    findAll()
 * @method ScholarshipOrganization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipOrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScholarshipOrganization::class);
    }

    /**
     * Paginated organization list with the count of scholarships linked to each.
     *
     * @return array{organizations: array<int, array{id:int, organization:string, scholarship_count:int}>, totalRows: int}
     */
    public function paginatedWithScholarshipCount(int $page, int $limit, ?string $searchTerm = null): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createQueryBuilder('o')
            ->select('o.id AS id', 'o.organization AS organization', 'COUNT(ol.organization) AS scholarship_count')
            ->leftJoin('o.scholarshipLinks', 'ol')
            ->groupBy('o.id')
            ->orderBy('o.organization', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $countQb = $this->createQueryBuilder('o')->select('COUNT(DISTINCT o.id)');

        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $qb->andWhere('o.organization LIKE :term')->setParameter('term', '%' . trim($searchTerm) . '%');
            $countQb->andWhere('o.organization LIKE :term')->setParameter('term', '%' . trim($searchTerm) . '%');
        }

        $rows = array_map(
            static fn (array $r) => [
                'id' => (int) $r['id'],
                'organization' => $r['organization'],
                'scholarship_count' => (int) $r['scholarship_count'],
            ],
            $qb->getQuery()->getArrayResult()
        );

        return [
            'organizations' => $rows,
            'totalRows' => (int) $countQb->getQuery()->getSingleScalarResult(),
        ];
    }

    /**
     * Case-insensitive lookup by name, for dedupe on create / CSV import.
     */
    public function findOneByNameCI(string $organization): ?ScholarshipOrganization
    {
        return $this->createQueryBuilder('o')
            ->where('LOWER(o.organization) = LOWER(:organization)')
            ->setParameter('organization', trim($organization))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
