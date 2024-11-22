<?php

namespace App\Repository\Programs;

use App\Entity\Programs\ProgramWebsites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramWebsites>
 */
class ProgramWebsitesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgramWebsites::class);
    }

		public function paginatedWebsites($currentPage, $pageSize): array
		{
			$offset = ($currentPage - 1) * $pageSize;

			$rows = $this->createQueryBuilder('w')
				->orderBy('w.program', 'ASC')
				->setFirstResult($offset)
				->setMaxResults($pageSize)
				->getQuery()
				->getResult();

			$numRows = $this->createQueryBuilder('w')
				->select('COUNT(w.id)')
				->getQuery()
			  ->getSingleScalarResult();

			return [
				'websites' => $rows,
				'totalRows' => $numRows
			];
		}

	public function searchResults($searchTerm): array {
		return $this->createQueryBuilder('w')
			->where('w.program LIKE :searchTerm')
			->orderBy('w.program', 'ASC')
			->setMaxResults(30)
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->getQuery()
			->getResult();
	}
}
