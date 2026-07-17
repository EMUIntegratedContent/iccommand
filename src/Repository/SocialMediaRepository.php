<?php

namespace App\Repository;

use App\Entity\SocialMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SocialMedia>
 *
 * @method SocialMedia|null find($id, $lockMode = null, $lockVersion = null)
 * @method SocialMedia|null findOneBy(array $criteria, array $orderBy = null)
 * @method SocialMedia[]    findAll()
 * @method SocialMedia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SocialMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialMedia::class);
    }

    public function save(SocialMedia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SocialMedia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Gets the social media entities with pagination, ordered by name.
     * @param $currentPage
     * @param $pageSize
     * @param $searchTerm
     * @return array
     */
    public function paginatedSocialMedia($currentPage, $pageSize, $searchTerm = '')
    {
        $qb = $this->createQueryBuilder('s');

        if (!empty($searchTerm)) {
            $qb->where('s.name LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $qb->orderBy('s.name', 'ASC');

        $query = $qb->getQuery();
        $query->setFirstResult(($currentPage - 1) * $pageSize);
        $query->setMaxResults($pageSize);

        $socialMedia = $query->getResult();

        // Get total count
        $countQb = $this->createQueryBuilder('s');
        if (!empty($searchTerm)) {
            $countQb->where('s.name LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }
        $countQb->select('COUNT(s.id)');
        $totalRows = $countQb->getQuery()->getSingleScalarResult();

        return [
            'socialMedia' => $socialMedia,
            'totalRows' => $totalRows
        ];
    }

    /**
     * Get social media entities that match the search term (by name).
     * @param $searchTerm
     * @return array
     */
    public function searchResultsSocialMedia($searchTerm)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('s.name', 'ASC')
            ->setMaxResults(10);

        return $qb->getQuery()->getResult();
    }
}
