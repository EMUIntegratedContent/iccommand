<?php

namespace App\Repository\Map;

use App\Entity\Map\MapBuildingType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MapBuildingType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapBuildingType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapBuildingType[]    findAll()
 * @method MapBuildingType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapBuildingTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapBuildingType::class);
    }

    public function findAllBuildingTypesWithFields(array $fields)
    {
        return $this->createQueryBuilder('bt')
            ->select($fields)
            ->orderBy('bt.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
