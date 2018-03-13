<?php

namespace App\Repository\Map;

use App\Entity\Map\MapExhibit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapExhibit|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapExhibit|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapExhibit[]    findAll()
 * @method MapExhibit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapExhibitRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapExhibit::class);
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
