<?php

namespace App\Repository;

use App\Entity\OuCampusEvents;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OuCampusEvents|null find($id, $lockMode = null, $lockVersion = null)
 * @method OuCampusEvents|null findOneBy(array $criteria, array $orderBy = null)
 * @method OuCampusEvents[]    findAll()
 * @method OuCampusEvents[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OuCampusEventsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OuCampusEvents::class);
    }

    // /**
    //  * @return OuCampusEvents[] Returns an array of OuCampusEvents objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OuCampusEvents
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
