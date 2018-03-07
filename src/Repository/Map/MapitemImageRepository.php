<?php

namespace App\Repository\Map;

use App\Entity\Map\MapitemImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MapitemImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapitemImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapitemImage[]    findAll()
 * @method MapitemImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapitemImageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapitemImage::class);
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
