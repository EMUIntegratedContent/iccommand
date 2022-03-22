<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\VideoRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VideoRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method VideoRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method VideoRequest[]    findAll()
 * @method VideoRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VideoRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VideoRequest::class);
    }

//    /**
//     * @return VideoRequest[] Returns an array of VideoRequest objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?VideoRequest
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
