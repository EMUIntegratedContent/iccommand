<?php

namespace App\Repository\Programs;

use App\Entity\Programs\Programs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Programs>
 */
class ProgramsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Programs::class);
    }

	public function paginatedPrograms($currentPage, $pageSize, $catalog): array
	{
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Build the query for getting paginated records
		$programs = $this->createQueryBuilder('p')
			->where('p.catalog = :catalog')
			->orderBy('p.program', 'ASC')
			->setFirstResult($offset)
			->setMaxResults($pageSize)
			->setParameter('catalog', $catalog)
			->getQuery()
			->getResult();

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

	public function searchResults($searchTerm, $catalog): array {
		// Build the query for getting paginated records
		return $this->createQueryBuilder('p')
			->where('p.catalog = :catalog')
			->andWhere('p.program LIKE :searchTerm')
			->orderBy('p.program', 'ASC')
			->setMaxResults(30)
			->setParameter('catalog', $catalog)
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->getQuery()
			->getResult();
	}
}
