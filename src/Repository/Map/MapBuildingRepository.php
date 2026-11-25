<?php

namespace App\Repository\Map;

use App\Entity\Map\MapBuilding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MapBuilding|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapBuilding|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapBuilding[]    findAll()
 * @method MapBuilding[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapBuildingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapBuilding::class);
    }


    /**
     * Every building's id and name, sorted by name, for pickers.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function findOptions(): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('b.id', 'b.name')
            ->orderBy('b.name', 'ASC')
            ->addOrderBy('b.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(fn (array $row) => ['id' => (int) $row['id'], 'name' => (string) $row['name']], $rows);
    }

    public function findAllBuildingsWithFields(array $fields)
    {
        return $this->createQueryBuilder('b')
            ->select($fields)
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
