<?php

namespace App\Repository\Map;

use App\Entity\Map\MapDispenser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MapDispenser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapDispenser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapDispenser[]    findAll()
 * @method MapDispenser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapDispenserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapDispenser::class);
    }

//    /**
//     * @return MapDispenser[] Returns an array of MapDispenser objects
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
    public function findOneBySomeField($value): ?MapDispenser
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
