<?php

namespace App\Repository\PhotoRequest;

use App\Entity\PhotoRequest\PhotoRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @method PhotoRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoRequest[]    findAll()
 * @method PhotoRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoRequestRepository extends ServiceEntityRepository
{
	protected ObjectManager $em;

	public function __construct(ManagerRegistry $doctrine)
	{
		parent::__construct($doctrine, PhotoRequest::class);
		$this->em = $doctrine->getManager();
	}

	public function paginatedPhotoRequests($currentPage, $pageSize, $statuses = null, $category = null): array
	{
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Build query builder
		$qb = $this->em->createQueryBuilder()
			->select('p', 'u')
			->from(PhotoRequest::class, 'p')
			->leftJoin('p.assignedTo', 'u');

		// Add status filter for multiple statuses
		$this->applyStatusFilter($qb, $statuses);

		// Add category filter
		if ($category) {
			$qb->andWhere('p.category = :category');
			$qb->setParameter('category', $category);
		}

		// Get paginated photo requests
		$photoRequests = $qb->orderBy('p.shootDate', 'DESC')->addOrderBy('p.submitted', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($pageSize)
			->getQuery()
			->getResult();

		// Count the total number of rows
		$countQb = $this->em->createQueryBuilder()
			->select('COUNT(p.id)')
			->from(PhotoRequest::class, 'p');

		// Add same status filter to count query
		$this->applyStatusFilter($countQb, $statuses);

		// Add same category filter to count query
		if ($category) {
			$countQb->andWhere('p.category = :category');
			$countQb->setParameter('category', $category);
		}

		$totalRequests = $countQb->getQuery()->getSingleScalarResult();

		return [
			'photoRequests' => $photoRequests,
			'totalRows' => $totalRequests
		];
	}

	public function getCategoriesWithCounts($statuses = null): array
	{
		// Build query builder for categories with counts
		$qb = $this->em->createQueryBuilder()
			->select('p.category', 'COUNT(p.id) as count')
			->from(PhotoRequest::class, 'p')
			->where('p.category IS NOT NULL')
			->andWhere('p.category != \'\'')
			->groupBy('p.category')
			->orderBy('p.category', 'ASC');

		// Add status filter if provided
		$this->applyStatusFilter($qb, $statuses);

		$results = $qb->getQuery()->getResult();

		// Format the results
		$categories = [];
		foreach ($results as $result) {
			$categories[] = [
				'category' => $result['category'],
				'count' => $result['count']
			];
		}

		return $categories;
	}

	public function searchResults($searchTerm): array
	{
		// Build the query for getting search results
		return $this->em->createQueryBuilder()
			->select('p', 'u')
			->from(PhotoRequest::class, 'p')
			->leftJoin('p.assignedTo', 'u')
			->where('p.firstName LIKE :searchTerm')
			->orWhere('p.lastName LIKE :searchTerm')
			->orWhere('p.description LIKE :searchTerm')
			->orderBy('p.submitted', 'DESC')
			->setMaxResults(30)
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->getQuery()
			->getResult();
	}

	public function getPhotoRequest($id)
	{
		return $this->em->createQueryBuilder()
			->select('p', 'u')
			->from(PhotoRequest::class, 'p')
			->leftJoin('p.assignedTo', 'u')
			->where('p.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getOneOrNullResult();
	}

	public function getPhotoRequestEntity($id)
	{
		return $this->find($id);
	}

	/**
	 * DQL condition for each status filter the UI offers. Unknown values are ignored,
	 * so user input never reaches the query text.
	 */
	private const STATUS_CONDITIONS = [
		'declined' => 'p.declined = 1',
		'complete' => 'p.completed = 1 AND p.declined = 0',
		'pending' => "(p.status IS NULL OR p.status = '') AND p.completed = 0 AND p.declined = 0",
		'WC' => "p.status = 'WC' AND p.completed = 0 AND p.declined = 0",
		'IP' => "p.status = 'IP' AND p.completed = 0 AND p.declined = 0",
		'DG' => "p.status = 'DG' AND p.completed = 0 AND p.declined = 0",
	];

	private function applyStatusFilter(QueryBuilder $qb, $statuses): void
	{
		if (!is_array($statuses)) {
			return;
		}
		$conditions = [];
		foreach ($statuses as $status) {
			if (is_string($status) && isset(self::STATUS_CONDITIONS[$status])) {
				$conditions[$status] = '(' . self::STATUS_CONDITIONS[$status] . ')';
			}
		}
		if ($conditions) {
			$qb->andWhere(implode(' OR ', $conditions));
		}
	}
}
