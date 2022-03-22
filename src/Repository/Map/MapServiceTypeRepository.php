<?php

namespace App\Repository\Map;

use App\Entity\Map\MapServiceType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MapServiceType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapServiceType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapServiceType[]    findAll()
 * @method MapServiceType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapServiceTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapServiceType::class);
    }

//    /**
//     * @return MapServiceType[] Returns an array of MapServiceType objects
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
    public function findOneBySomeField($value): ?MapServiceType
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
