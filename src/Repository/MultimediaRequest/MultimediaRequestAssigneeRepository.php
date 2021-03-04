<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MultimediaRequestAssignee|null find($id, $lockMode = null, $lockVersion = null)
 * @method MultimediaRequestAssignee|null findOneBy(array $criteria, array $orderBy = null)
 * @method MultimediaRequestAssignee[]    findAll()
 * @method MultimediaRequestAssignee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MultimediaRequestAssigneeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MultimediaRequestAssignee::class);
    }

//    /**
//     * @return MultimediaRequestAssignee[] Returns an array of MultimediaRequestAssignee objects
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
    public function findOneBySomeField($value): ?MultimediaRequestAssignee
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
