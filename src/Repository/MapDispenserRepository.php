<?php

namespace App\Repository;

use App\Entity\MapDispenser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MapDispenser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapDispenser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapDispenser[]    findAll()
 * @method MapDispenser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapDispenserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapDispenser::class);
    }
}
