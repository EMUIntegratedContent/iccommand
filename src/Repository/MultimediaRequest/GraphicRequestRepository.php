<?php

namespace App\Repository\MultimediaRequest;

use App\Entity\GraphicRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GraphicRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method GraphicRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method GraphicRequest[]    findAll()
 * @method GraphicRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GraphicRequestRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GraphicRequest::class);
    }

//    /**
//     * @return GraphicRequest[] Returns an array of GraphicRequest objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GraphicRequest
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
