<?php

namespace App\Repository;

use App\Entity\DirectoryDepartments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DirectoryDepartments|null find($id, $lockMode = null, $lockVersion = null)
 * @method DirectoryDepartments|null findOneBy(array $criteria, array $orderBy = null)
 * @method DirectoryDepartments[]    findAll()
 * @method DirectoryDepartments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DirectoryDepartmentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DirectoryDepartments::class);
    }

    // /**
    //  * @return DirectoryDepartments[] Returns an array of DirectoryDepartments objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DirectoryDepartments
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
