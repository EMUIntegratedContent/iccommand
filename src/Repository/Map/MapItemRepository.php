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

    /**
     * Return the item type ("discr" field). Need to use raw SQL since discr is not mapped by Doctrine
     * TUTORIAL: https://symfony.com/doc/current/doctrine.html#querying-with-dql-or-sql
     */
    public function findItemType($id)
    {
      $conn = $this->getEntityManager()->getConnection();

      $sql = '
          SELECT m.discr FROM map_item m
          WHERE m.id = :id
          ';
      $stmt = $conn->prepare($sql);
      $stmt->execute(['id' => $id]);

      $mapItem = $stmt->fetchAll();
      return $mapItem[0]['discr'];
    }

}
