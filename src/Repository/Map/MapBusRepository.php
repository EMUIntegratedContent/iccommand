<?php

namespace App\Repository\Map;

use App\Entity\Map\MapBus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapBus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapBus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapBus[]    findAll()
 * @method MapBus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapBusRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapBus::class);
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
