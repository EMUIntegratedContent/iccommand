<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\HeadshotRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HeadshotRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeadshotRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeadshotRequest[]    findAll()
 * @method HeadshotRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeadshotRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeadshotRequest::class);
    }

//    /**
//     * @return HeadshotRequest[] Returns an array of HeadshotRequest objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HeadshotRequest
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
