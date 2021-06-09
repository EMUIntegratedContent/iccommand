<?php

namespace App\Repository\Map;

use App\Entity\Map\MapBuilding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MapBuilding|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapBuilding|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapBuilding[]    findAll()
 * @method MapBuilding[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapBuildingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapBuilding::class);
    }


    public function findAllBuildingsWithFields(array $fields)
    {
        return $this->createQueryBuilder('b')
            ->select($fields)
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
