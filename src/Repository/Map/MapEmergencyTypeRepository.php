<?php

namespace App\Repository\Map;

use App\Entity\Map\MapEmergencyType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapEmergencyType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapEmergencyType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapEmergencyType[]    findAll()
 * @method MapEmergencyType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapEmergencyTypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapEmergencyType::class);
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
