<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\MultimediaRequestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MultimediaRequestStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MultimediaRequestStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MultimediaRequestStatus[]    findAll()
 * @method MultimediaRequestStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MultimediaRequestStatusRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MultimediaRequestStatus::class);
    }

//    /**
//     * @return MultimediaRequestStatus[] Returns an array of MultimediaRequestStatus objects
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
    public function findOneBySomeField($value): ?MultimediaRequestStatus
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
