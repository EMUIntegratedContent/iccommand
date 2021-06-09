<?php

namespace App\Repository\Map;

use App\Entity\Map\MapBathroom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MapBathroom|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapBathroom|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapBathroom[]    findAll()
 * @method MapBathroom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapBathroomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapBathroom::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('m')
            ->where('m.something = :value')->setParameter('value', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
