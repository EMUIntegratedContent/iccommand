<?php

namespace App\Repository\Programs;

use App\Entity\Programs\ProgramWebsites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramWebsites>
 */
class ProgramWebsitesRepository extends ServiceEntityRepository{
	protected ObjectManager $em;

	public function __construct(ManagerRegistry $doctrine){
		parent::__construct($doctrine, ProgramWebsites::class);
		$this->em = $doctrine->getManager('programs');
	}

	public function getWebsiteByProg($progName): ?ProgramWebsites{
		// IMPORTANT: w.program refers to programs_programs.full_name (not programs_programs.program)!!
		return $this->em->createQueryBuilder()
			->from(ProgramWebsites::class, 'w')
			->select('w')
			->where('w.program = :progName')
			->setParameter('progName', $progName)
			->getQuery()
			->getOneOrNullResult();
	}

	public function paginatedWebsites($currentPage, $pageSize): array{
		$offset = ($currentPage - 1) * $pageSize;
		// Do raw SQL because the JOIN on program_websites doesn't use FK relationship and thus confuses doctrine
		$websitesSql = "
			SELECT w.*, p.id AS prog_id
			FROM programs.program_websites w
			LEFT JOIN programs.program_programs p ON p.full_name = w.program
			ORDER BY w.url, w.program ASC
			LIMIT $offset, $pageSize
		";

		$stmt = $this->em->getConnection()->prepare($websitesSql);
		$websites = $stmt->executeQuery()->fetchAllAssociative();

		$numRows = $this->em->createQueryBuilder()
			->select('COUNT(w.id)')
			->from(ProgramWebsites::class, 'w')
			->getQuery()
			->getSingleScalarResult();

		return [
			'websites' => $websites,
			'totalRows' => $numRows
		];
	}

	public function searchResults($searchTerm): array{
		$websitesSql = "
			SELECT w.*, p.id AS prog_id, CONCAT(w.url,' -> ', w.program) AS display
			FROM programs.program_websites w
			LEFT JOIN programs.program_programs p ON p.full_name = w.program
			WHERE w.program LIKE :searchTerm OR w.url LIKE :searchTerm
			ORDER BY w.url, w.program ASC
			LIMIT 30
		";

		$stmt = $this->em->getConnection()->prepare($websitesSql);
		return $stmt->executeQuery(['searchTerm' => "%$searchTerm%"])->fetchAllAssociative();
	}
}
