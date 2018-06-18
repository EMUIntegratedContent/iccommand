<?php

namespace App\Repository\Redirect;

use App\Entity\Redirect\Uncaught;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Uncaught|null find($id, $lockMode = null, $lockVersion = null)
 * @method Uncaught|null findOneBy(array $criteria, array $orderBy = null)
 * @method Uncaught[]    findAll()
 * @method Uncaught[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UncaughtRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Uncaught::class);
    }

//    /**
//     * @return Uncaught[] Returns an array of Uncaught objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Uncaught
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
