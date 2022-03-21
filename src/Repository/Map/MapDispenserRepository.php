<?php

namespace App\Repository\Map;

use App\Entity\Map\MapDispenser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapDispenser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapDispenser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapDispenser[]    findAll()
 * @method MapDispenser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapDispenserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapDispenser::class);
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
