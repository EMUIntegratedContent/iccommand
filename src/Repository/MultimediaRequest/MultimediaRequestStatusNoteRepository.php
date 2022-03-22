<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\MultimediaRequestStatusNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MultimediaRequestStatusNote|null find($id, $lockMode = null, $lockVersion = null)
 * @method MultimediaRequestStatusNote|null findOneBy(array $criteria, array $orderBy = null)
 * @method MultimediaRequestStatusNote[]    findAll()
 * @method MultimediaRequestStatusNote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MultimediaRequestStatusNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MultimediaRequestStatusNote::class);
    }

//    /**
//     * @return MultimediaRequestStatusNote[] Returns an array of MultimediaRequestStatusNote objects
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
    public function findOneBySomeField($value): ?MultimediaRequestStatusNote
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
