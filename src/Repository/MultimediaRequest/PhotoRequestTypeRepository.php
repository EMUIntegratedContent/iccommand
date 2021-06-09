<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\MultimediaRequest\PhotoRequestType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PhotoRequestType|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoRequestType|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoRequestType[]    findAll()
 * @method PhotoRequestType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoRequestTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoRequestType::class);
    }

//    /**
//     * @return PhotoRequestType[] Returns an array of PhotoRequestType objects
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
    public function findOneBySomeField($value): ?PhotoRequestType
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
