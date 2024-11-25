<?php

namespace App\Repository\Programs;

use App\Entity\Programs\ProgramWebsites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramWebsites>
 */
class ProgramWebsitesRepository extends ServiceEntityRepository{
	protected Connection $em;

	public function __construct(ManagerRegistry $registry){
		parent::__construct($registry, ProgramWebsites::class);
		$this->em = $this->getEntityManager()->getConnection();
	}

	public function getWebsiteByProg($progName): ?ProgramWebsites{
		return $this->createQueryBuilder('w')
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
			LEFT JOIN programs.program_programs p ON p.program = w.program
			ORDER BY w.url, w.program ASC
			LIMIT $offset, $pageSize
		";

		$stmt = $this->em->prepare($websitesSql);
		$websites = $stmt->executeQuery()->fetchAllAssociative();

		$numRows = $this->createQueryBuilder('w')
			->select('COUNT(w.id)')
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
			LEFT JOIN programs.program_programs p ON p.program = w.program
			WHERE w.program LIKE :searchTerm OR w.url LIKE :searchTerm
			ORDER BY w.url, w.program ASC
			LIMIT 30
		";

		$stmt = $this->em->prepare($websitesSql);
		return $stmt->executeQuery(['searchTerm' => "%$searchTerm%"])->fetchAllAssociative();

//		return $this->createQueryBuilder('w')
//			->select('w.id, CONCAT(w.url, \' -> \', w.program) AS display')
//			->where('w.program LIKE :searchTerm')
//			->orWhere('w.url LIKE :searchTerm')
//			->orderBy('w.url, w.program', 'ASC')
//			->setMaxResults(30)
//			->setParameter('searchTerm', '%'.$searchTerm.'%')
//			->getQuery()
//			->getResult();
	}
}
