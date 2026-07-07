<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\Scholarship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the scholarships.
 * @method Scholarship|null find($id, $lockMode = null, $lockVersion = null)
 * @method Scholarship|null findOneBy(array $criteria, array $orderBy = null)
 * @method Scholarship[]    findAll()
 * @method Scholarship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scholarship::class);
    }

    /**
     * A page of scholarships plus the total row count, optionally limited to active ones.
     */
    public function paginatedScholarships(int $currentPage, int $pageSize, ?bool $activeOnly = null): array
    {
        $offset = ($currentPage - 1) * $pageSize;

        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize);
        if ($activeOnly === true) {
            $qb->where('s.active = true');
        }
        $scholarships = $qb->getQuery()->getResult();

        $countQb = $this->createQueryBuilder('s')->select('COUNT(s.id)');
        if ($activeOnly === true) {
            $countQb->where('s.active = true');
        }
        $totalRows = $countQb->getQuery()->getSingleScalarResult();

        return [
            'scholarships' => $scholarships,
            'totalRows' => $totalRows
        ];
    }

    /**
     * A capped list of scholarships whose title or keywords match the search term.
     */
    public function searchScholarships(string $searchTerm): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.title LIKE :searchTerm')
            ->orWhere('s.keywords LIKE :searchTerm')
            ->orderBy('s.title', 'ASC')
            ->setMaxResults(30)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();
    }
}
