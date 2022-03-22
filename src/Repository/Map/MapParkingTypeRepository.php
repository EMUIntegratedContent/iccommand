<?php

namespace App\Repository\Map;

use App\Entity\Map\MapParkingType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MapParkingType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapParkingType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapParkingType[]    findAll()
 * @method MapParkingType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapParkingTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapParkingType::class);
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
