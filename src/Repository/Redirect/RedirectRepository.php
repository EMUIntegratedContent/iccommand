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

	public function paginatedRedirects(int $currentPage, int $pageSize, string $itemType): array
	{
		if($itemType === 'broken') {
			$itemType = 'redirect of broken link';
		} else if ($itemType === 'shortened') {
			$itemType = 'redirect of shortened link';
		} else {
			return [];
		}
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Build the query for getting paginated records
		$redirects = $this->createQueryBuilder('r')
			->where('r.itemType = :itemType')
			->orderBy('r.fromLink', 'ASC')
			->setFirstResult($offset)
			->setMaxResults($pageSize)
			->setParameter('itemType', $itemType)
			->getQuery()
			->getResult();

		// Count the total number of rows
		$totalRedirs = $this->createQueryBuilder('r')
			->select('COUNT(r.id)')
			->where('r.itemType = :itemType')
			->setParameter('itemType', $itemType)
			->getQuery()
			->getSingleScalarResult();

		return [
			'redirects' => $redirects,
			'totalRows' => $totalRedirs
		];
	}

	public function searchResultsRedirects($searchTerm, $itemType): array {
		if($itemType === 'broken') {
			$itemType = 'redirect of broken link';
		} else if ($itemType === 'shortened') {
			$itemType = 'redirect of shortened link';
		} else {
			return [];
		}

		// Build the query for getting paginated records
		return $this->createQueryBuilder('r')
			->select('CONCAT(r.fromLink, \' -> \', r.toLink) AS linkDescr, r.id')
			->where('r.fromLink LIKE :searchTerm')
			->orWhere('r.toLink LIKE :searchTerm')
			->andWhere('r.itemType = :type')
			->orderBy('r.fromLink', 'ASC')
			->setMaxResults(30)
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->setParameter('type', $itemType)
			->getQuery()
			->getResult();
	}
}
