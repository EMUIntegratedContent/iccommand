<?php

namespace App\Repository\Map;

use App\Entity\Map\MapParking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapParking|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapParking|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapParking[]    findAll()
 * @method MapParking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapParkingRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapParking::class);
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
