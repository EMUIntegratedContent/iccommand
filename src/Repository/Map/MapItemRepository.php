<?php

namespace App\Repository\Map;

use App\Entity\Map\MapItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapItem[]    findAll()
 * @method MapItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapItemRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapItem::class);
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
