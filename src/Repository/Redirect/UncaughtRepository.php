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

  /**
   * Record a visit to an uncaught (404) URL: insert it with one visit, or add
   * a visit if it is already logged. A single atomic statement, so concurrent
   * requests for the same URL neither fail on the unique index nor lose counts.
   * Relies on the unique index on uncaught.link.
   */
  public function recordVisit(string $link): void {
    $this->getEntityManager()->getConnection()->executeStatement(
      'INSERT INTO uncaught (link, visits, is_recommended) VALUES (:link, 1, 1)
       ON DUPLICATE KEY UPDATE visits = visits + 1',
      ['link' => $link]
    );
  }

  /**
   * Add a visit to an already-logged uncaught URL.
   * @return bool false if the URL is not logged
   */
  public function incrementVisits(string $link): bool {
    return $this->getEntityManager()->getConnection()->executeStatement(
      'UPDATE uncaught SET visits = visits + 1 WHERE link = :link',
      ['link' => $link]
    ) > 0;
  }
}
