<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\PhotoHeadshotDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PhotoHeadshotDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoHeadshotDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoHeadshotDate[]    findAll()
 * @method PhotoHeadshotDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoHeadshotDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoHeadshotDate::class);
    }

    /**
     * @return PhotoHeadshotDate[] Returns an array of PhotoHeadshotDate objects
     *
     * Unfortunately, we can't select all fields and group by just dateOfShoot, so just return dateOfShoot field
     * https://dev.mysql.com/doc/refman/8.0/en/group-by-handling.html
     */
    public function findByGroupped($startDate = null, $endDate = null)
    {
        $query = $this->createQueryBuilder('p')
            ->select('p.dateOfShoot');

        // Limit results by date range, if a start and end date are given
        if($startDate && $endDate){
            $query = $query->andWhere('p.dateOfShoot BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
            ;
        }

        $query = $query->groupBy('p.dateOfShoot')
            ->orderBy('p.dateOfShoot', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $query;
    }

    public function findByFutureDatetime(){
        $now = new \DateTime();
        return $this->createQueryBuilder('p')
            ->andWhere('p.dateOfShoot >= :now')
            ->setParameter('now', $now->format('Y-m-d'))
            ->orderBy('p.dateOfShoot', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

}
