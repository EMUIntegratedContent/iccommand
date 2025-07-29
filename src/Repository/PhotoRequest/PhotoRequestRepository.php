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

	public function paginatedPhotoRequests($currentPage, $pageSize, $status = null): array
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
				$qb->andWhere('p.completed = 1');
			} elseif ($status === 'pending') {
				$qb->andWhere('p.status IS NULL OR p.status = \'\'');
				$qb->andWhere('p.completed = 0');
			} else {
				$qb->andWhere('p.status = :status AND p.completed = 0');
				$qb->setParameter('status', $status);
			}
		}

		// Get paginated photo requests
		$photoRequests = $qb->orderBy('p.submitted', 'DESC')
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
				$countQb->andWhere('p.completed = 1');
			} elseif ($status === 'pending') {
				$countQb->andWhere('p.completed = 0');
			} else {
				$countQb->andWhere('p.status = :status AND p.completed = 0');
				$countQb->setParameter('status', $status);
			}
		}

		$totalRequests = $countQb->getQuery()->getSingleScalarResult();

		return [
			'photoRequests' => $photoRequests,
			'totalRows' => $totalRequests
		];
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
