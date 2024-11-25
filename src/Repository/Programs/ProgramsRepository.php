<?php

namespace App\Repository\Programs;

use App\Entity\Programs\Programs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

/**
 * @extends ServiceEntityRepository<Programs>
 */
class ProgramsRepository extends ServiceEntityRepository{

	protected Connection $em;
	public function __construct(ManagerRegistry $registry){
		parent::__construct($registry, Programs::class);
		$this->em = $this->getEntityManager()->getConnection();
	}

	public function getProgram($id) {
		// Do raw SQL because the JOIN on program_websites doesn't use FK relationship and thus confuses doctrine
		$programSql = "
			SELECT p.*, w.id AS website_id, w.url
			FROM programs.program_programs p
			LEFT JOIN programs.program_websites w ON p.program = w.program
			WHERE p.id = :id
		";

		$stmt = $this->em->prepare($programSql);
		return $stmt->executeQuery([
			'id' => $id
		])->fetchAssociative();
	}

	public function paginatedPrograms($currentPage, $pageSize, $catalog): array{
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Do raw SQL because the JOIN on program_websites doesn't use FK relationship and thus confuses doctrine
		$programSql = "
			SELECT p.*, w.id AS website_id, w.url
			FROM programs.program_programs p
			JOIN programs.program_websites w ON p.program = w.program
			WHERE p.catalog = :catalog
			ORDER BY p.program ASC
			LIMIT $offset, $pageSize
		";

		$stmt = $this->em->prepare($programSql);
		$programs = $stmt->executeQuery([
			'catalog' => $catalog
		])->fetchAllAssociative();

		// Count the total number of rows
		$totalProgs = $this->createQueryBuilder('p')
			->select('COUNT(p.id)')
			->where('p.catalog = :catalog')
			->setParameter('catalog', $catalog)
			->getQuery()
			->getSingleScalarResult();

		return [
			'programs' => $programs,
			'totalRows' => $totalProgs
		];
	}

	public function searchResults($searchTerm, $catalog): array{
		// Build the query for getting paginated records
		return $this->createQueryBuilder('p')
			->where('p.catalog = :catalog')
			->andWhere('p.program LIKE :searchTerm')
			->orderBy('p.program', 'ASC')
			->setMaxResults(30)
			->setParameter('catalog', $catalog)
			->setParameter('searchTerm', '%'.$searchTerm.'%')
			->getQuery()
			->getResult();
	}
}
