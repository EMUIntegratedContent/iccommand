<?php

namespace App\Repository\PhotoRequest;

use App\Entity\PhotoRequest\PhotoRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

	public function paginatedPhotoRequests($currentPage, $pageSize, $status = null, $category = null): array
	{
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Build query builder
		$qb = $this->em->createQueryBuilder()
			->select('p', 'u')
			->from(PhotoRequest::class, 'p')
			->leftJoin('p.assignedTo', 'u');

		// Add status filter
		if ($status) {
			if ($status === 'declined') {
				$qb->andWhere('p.declined = 1');
			} elseif ($status === 'complete') {
				$qb->andWhere('p.completed = 1 AND p.declined = 0');
			} elseif ($status === 'pending') {
				$qb->andWhere('(p.status IS NULL OR p.status = \'\') AND p.completed = 0 AND p.declined = 0');
			} elseif ($status === 'WC') {
				$qb->andWhere('(p.status = \'WC\') AND p.completed = 0 AND p.declined = 0');
			} elseif ($status === 'IP') {
				$qb->andWhere('(p.status = \'IP\') AND p.completed = 0 AND p.declined = 0');
			} elseif ($status === 'DG') {
				$qb->andWhere('(p.status = \'DG\') AND p.completed = 0 AND p.declined = 0');
			} else {
				$qb->andWhere('p.status = :status AND p.completed = 0 AND p.declined = 0');
				$qb->setParameter('status', $status);
			}
		}

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
		if ($status) {
			if ($status === 'declined') {
				$countQb->andWhere('p.declined = 1');
			} elseif ($status === 'complete') {
				$countQb->andWhere('p.completed = 1 AND p.declined = 0');
			} elseif ($status === 'pending') {
				$countQb->andWhere('(p.status IS NULL OR p.status = \'\') AND p.completed = 0 AND p.declined = 0');
			} elseif ($status === 'WC') {
				$countQb->andWhere('(p.status = \'WC\') AND p.completed = 0 AND p.declined = 0');
			} elseif ($status === 'IP') {
				$countQb->andWhere('(p.status = \'IP\') AND p.completed = 0 AND p.declined = 0');
			} elseif ($status === 'DG') {
				$countQb->andWhere('(p.status = \'DG\') AND p.completed = 0 AND p.declined = 0');
			} else {
				$countQb->andWhere('p.status = :status AND p.completed = 0 AND p.declined = 0');
				$countQb->setParameter('status', $status);
			}
		}

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

	public function getCategoriesWithCounts($status = null): array
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
		if ($status) {
			if ($status === 'declined') {
				$qb->andWhere('p.declined = 1');
			} elseif ($status === 'complete') {
				$qb->andWhere('p.completed = 1');
			} elseif ($status === 'pending') {
				$qb->andWhere('(p.status IS NULL OR p.status = \'\') AND p.completed = 0 AND p.declined = 0');
			} else {
				$qb->andWhere('p.status = :status AND p.completed = 0 AND p.declined = 0');
				$qb->setParameter('status', $status);
			}
		}

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
}
