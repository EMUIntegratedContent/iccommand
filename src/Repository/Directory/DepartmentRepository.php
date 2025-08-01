<?php

namespace App\Repository\Directory;

use App\Entity\Directory\Department;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Department>
 *
 * @method Department|null find($id, $lockMode = null, $lockVersion = null)
 * @method Department|null findOneBy(array $criteria, array $orderBy = null)
 * @method Department[]    findAll()
 * @method Department[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepartmentRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Department::class);
  }

  public function save(Department $entity, bool $flush = false): void
  {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function remove(Department $entity, bool $flush = false): void
  {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Gets the departments with pagination.
   * @param $currentPage
   * @param $pageSize
   * @param $searchTerm
   * @return array
   */
  public function paginatedDepartments($currentPage, $pageSize, $searchTerm = '')
  {
    $qb = $this->createQueryBuilder('d');

    if (!empty($searchTerm)) {
      $qb->where('d.department LIKE :searchTerm OR d.searchTerms LIKE :searchTerm OR d.searchTerms LIKE :searchTermSeparator')
        ->setParameter('searchTerm', '%' . $searchTerm . '%')
        ->setParameter('searchTermSeparator', '%' . $searchTerm . '@@%');
    }

    $qb->orderBy('d.department', 'ASC');

    $query = $qb->getQuery();
    $query->setFirstResult(($currentPage - 1) * $pageSize);
    $query->setMaxResults($pageSize);

    $departments = $query->getResult();

    // Get total count
    $countQb = $this->createQueryBuilder('d');
    if (!empty($searchTerm)) {
      $countQb->where('d.department LIKE :searchTerm OR d.searchTerms LIKE :searchTerm OR d.searchTerms LIKE :searchTermSeparator')
        ->setParameter('searchTerm', '%' . $searchTerm . '%')
        ->setParameter('searchTermSeparator', '%' . $searchTerm . '@@%');
    }
    $countQb->select('COUNT(d.id)');
    $totalRows = $countQb->getQuery()->getSingleScalarResult();

    return [
      'departments' => $departments,
      'totalRows' => $totalRows
    ];
  }

  /**
   * Get departments that match the search term.
   * @param $searchTerm
   * @return array
   */
  public function searchResultsDepartments($searchTerm)
  {
    $qb = $this->createQueryBuilder('d');
    $qb->where('d.department LIKE :searchTerm OR d.searchTerms LIKE :searchTerm OR d.searchTerms LIKE :searchTermSeparator')
      ->setParameter('searchTerm', '%' . $searchTerm . '%')
      ->setParameter('searchTermSeparator', '%' . $searchTerm . '@@%')
      ->orderBy('d.department', 'ASC')
      ->setMaxResults(10);

    return $qb->getQuery()->getResult();
  }
}
