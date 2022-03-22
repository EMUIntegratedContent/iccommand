<?php

namespace App\Repository\Map;

use App\Entity\Map\MapExhibitType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MapExhibitType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapExhibitType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapExhibitType[]    findAll()
 * @method MapExhibitType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapExhibitTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapExhibitType::class);
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
