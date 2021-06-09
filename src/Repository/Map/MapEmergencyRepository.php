<?php

namespace App\Repository\Map;

use App\Entity\Map\MapEmergency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MapEmergency|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapEmergency|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapEmergency[]    findAll()
 * @method MapEmergency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapEmergencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapEmergency::class);
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
