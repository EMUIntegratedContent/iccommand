<?php

namespace App\Repository\Programs;

use App\Entity\Programs\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<Types>
 */
class TypesRepository extends ServiceEntityRepository
{
    protected ObjectManager $em;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Types::class);
        $this->em = $registry->getManager('programs');
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    //    /**
    //     * @return Types[] Returns an array of Types objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Types
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
