<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\PublicationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PublicationRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method PublicationRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method PublicationRequest[]    findAll()
 * @method PublicationRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PublicationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicationRequest::class);
    }

//    /**
//     * @return PublicationRequest[] Returns an array of PublicationRequest objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PublicationRequest
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
