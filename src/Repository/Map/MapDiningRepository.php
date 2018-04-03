<?php

namespace App\Repository\Map;

use App\Entity\MapDining;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapDining|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapDining|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapDining[]    findAll()
 * @method MapDining[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapDiningRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapDining::class);
    }

//    /**
//     * @return MapDining[] Returns an array of MapDining objects
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
    public function findOneBySomeField($value): ?MapDining
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
