<?php
namespace App\Repository\Redirect;

use App\Entity\Redirect\Redirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * This is the respository of the redirects.
 * @method Redirect|null find($id, $lockMode = null, $lockVersion = null)
 * @method Redirect|null findOneBy(array $criteria, array $orderBy = null)
 * @method Redirect[]    findAll()
 * @method Redirect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RedirectRepository extends ServiceEntityRepository {
  /*
   * The constructor of the repository of the redirects.
   * @param ManagerRegistry $registry The referer that references Doctrine
   * connections and entity managers.
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Redirect::class);
  }
}
