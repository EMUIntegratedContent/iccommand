<?php

namespace App\Repository\PhotoRequest;

use App\Entity\MultimediaRequest\PhotoRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @method PhotoRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoRequest[]    findAll()
 * @method PhotoRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoRequestRepository extends ServiceEntityRepository{
	protected ObjectManager $em;

	public function __construct(ManagerRegistry $doctrine){
		parent::__construct($doctrine, PhotoRequest::class);
		$this->em = $doctrine->getManager();
	}

	public function paginatedPhotoRequests($currentPage, $pageSize): array{
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Do raw SQL because the JOIN on program_websites doesn't use FK relationship and thus confuses doctrine
		$programSql = "
			SELECT p.*
			FROM photo_request p
			ORDER BY p.shoot_date ASC
			LIMIT $offset, $pageSize
		";

		$stmt = $this->em->getConnection()->prepare($programSql);
		$prs = $stmt->executeQuery([])->fetchAllAssociative();

		// Count the total number of rows
		$totalProgs = $this->em->createQueryBuilder()
			->select('COUNT(p.id)')
			->from(PhotoRequest::class, 'p')
			->getQuery()
			->getSingleScalarResult();

		return [
			'photoRequests' => $prs,
			'totalRows' => $totalProgs
		];
	}
}
