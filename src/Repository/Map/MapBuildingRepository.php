<?php

namespace App\Repository\Map;

use App\Entity\Map\MapBuilding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapBuilding|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapBuilding|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapBuilding[]    findAll()
 * @method MapBuilding[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapBuildingRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapBuilding::class);
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
