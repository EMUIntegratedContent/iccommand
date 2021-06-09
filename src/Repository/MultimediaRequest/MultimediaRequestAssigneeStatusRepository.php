<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\MultimediaRequestAssigneeStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MultimediaRequestAssigneeStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MultimediaRequestAssigneeStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MultimediaRequestAssigneeStatus[]    findAll()
 * @method MultimediaRequestAssigneeStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MultimediaRequestAssigneeStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MultimediaRequestAssigneeStatus::class);
    }

//    /**
//     * @return MultimediaRequestAssigneeStatus[] Returns an array of MultimediaRequestAssigneeStatus objects
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
    public function findOneBySomeField($value): ?MultimediaRequestAssigneeStatus
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
