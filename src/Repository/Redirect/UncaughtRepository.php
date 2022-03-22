<?php
namespace App\Repository\Redirect;

use App\Entity\Redirect\Uncaught;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * This is the repository of the uncaught items.
 * @method Uncaught|null find($id, $lockMode = null, $lockVersion = null)
 * @method Uncaught|null findOneBy(array $criteria, array $orderBy = null)
 * @method Uncaught[]    findAll()
 * @method Uncaught[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UncaughtRepository extends ServiceEntityRepository {
  /*
   * The constructor of the repository of the uncaught items.
   * @param ManagerRegistry $registry The referer that references Doctrine
   * connections and entity managers.
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Uncaught::class);
  }
}
