<?php

namespace App\Repository\Map;

use App\Entity\Map\MapService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MapService|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapService|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapService[]    findAll()
 * @method MapService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapService::class);
    }

//    /**
//     * @return MapService[] Returns an array of MapService objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MapService
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
