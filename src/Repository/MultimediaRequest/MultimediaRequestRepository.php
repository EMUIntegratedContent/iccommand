<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\MultimediaRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MultimediaRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method MultimediaRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method MultimediaRequest[]    findAll()
 * @method MultimediaRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MultimediaRequestRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MultimediaRequest::class);
    }

//    /**
//     * @return MultimediaRequest[] Returns an array of MultimediaRequest objects
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
    public function findOneBySomeField($value): ?MultimediaRequest
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
