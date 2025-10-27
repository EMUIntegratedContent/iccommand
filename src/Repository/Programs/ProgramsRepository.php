<?php

namespace App\Repository\Programs;

use App\Entity\Programs\Programs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<Programs>
 */
class ProgramsRepository extends ServiceEntityRepository{

	protected ObjectManager $em;
	public function __construct(ManagerRegistry $doctrine){
		parent::__construct($doctrine, Programs::class);
		$this->em = $doctrine->getManager('programs');
	}

	public function getProgramEntity($id): ?Programs{
		return $this->em->find(Programs::class, $id);
	}

	public function getProgram($id) {
		// Do raw SQL because the JOIN on program_websites doesn't use FK relationship and thus confuses doctrine
		$programSql = "
			SELECT p.*, w.id AS website_id, w.url,
				GROUP_CONCAT(DISTINCT pd.delivery_id) AS delivery_ids
			FROM programs.program_programs p
			LEFT JOIN programs.program_websites w ON p.program = w.program
			LEFT JOIN programs.program_delivery pd ON p.id = pd.program_id
			WHERE p.id = :id
			GROUP BY p.id
		";

		$stmt = $this->em->getConnection()->prepare($programSql);
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
			LEFT JOIN programs.program_websites w ON p.program = w.program
			WHERE p.catalog = :catalog
			ORDER BY p.full_name ASC
			LIMIT $offset, $pageSize
		";

		$stmt = $this->em->getConnection()->prepare($programSql);
		$programs = $stmt->executeQuery([
			'catalog' => $catalog
		])->fetchAllAssociative();

		// Count the total number of rows
		$totalProgs = $this->em->createQueryBuilder()
			->select('COUNT(p.id)')
			->from(Programs::class, 'p')
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
		return $this->em->createQueryBuilder()
			->select('p')
			->from(Programs::class, 'p')
			->where('p.catalog = :catalog')
			->andWhere('p.full_name LIKE :searchTerm')
			->orWhere('p.program LIKE :searchTerm')
			->orderBy('p.full_name', 'ASC')
			->setMaxResults(30)
			->setParameter('catalog', $catalog)
			->setParameter('searchTerm', '%'.$searchTerm.'%')
			->getQuery()
			->getResult();
	}
	public function getColleges(): array {
		$clgSql = "
			SELECT *
			FROM programs.program_colleges
			ORDER BY college ASC
		";

		$stmt = $this->em->getConnection()->prepare($clgSql);
		return $stmt->executeQuery()->fetchAllAssociative();
	}

	public function getDepartments(): array {
		$departmentsSql = "
			SELECT id, department
			FROM programs.program_departments
			ORDER BY department ASC
		";

		$stmt = $this->em->getConnection()->prepare($departmentsSql);
		return $stmt->executeQuery()->fetchAllAssociative();
	}

	public function getProgTypes(): array {
		$typesSql = "
			SELECT *
			FROM programs.program_types
			ORDER BY type ASC
		";

		$stmt = $this->em->getConnection()->prepare($typesSql);
		return $stmt->executeQuery()->fetchAllAssociative();
	}

	public function getDegrees(): array {
		$degreesSql = "
			SELECT *
			FROM programs.program_degrees
			ORDER BY degree ASC
		";

		$stmt = $this->em->getConnection()->prepare($degreesSql);
		return $stmt->executeQuery()->fetchAllAssociative();
	}

	/**
	 * Get all unique catalog IDs (they change every year).
	 * @return array
	 */
	public function getCatalogIds(): array {
		$catalogSql = "
			SELECT DISTINCT catalog, catalog_id
			FROM programs.program_programs
			ORDER BY catalog_id ASC
		";

		$stmt = $this->em->getConnection()->prepare($catalogSql);
		return $stmt->executeQuery()->fetchAllAssociative();
	}

	/**
	 * Updates the delivery modes for a program using a transaction.
	 * Deletes existing modes and inserts new ones.
	 * 
	 * @param int $programId The ID of the program
	 * @param array<int> $deliveryIds Array of delivery mode IDs
	 * @throws \Exception If database operation fails
	 */
	public function updateProgramDeliveryModes(int $programId, array $deliveryIds): void {
		try {
			$conn = $this->em->getConnection();
			$conn->beginTransaction();

			// Delete existing delivery modes
			$delSql = 'DELETE FROM programs.program_delivery WHERE program_id = :program_id';
			$stmt = $conn->prepare($delSql);
			$stmt->executeStatement(['program_id' => $programId]);

			// Insert new delivery modes
			$insSql = 'INSERT INTO programs.program_delivery (program_id, delivery_id) VALUES (:program_id, :delivery_id)';
			$ins = $conn->prepare($insSql);
			foreach ($deliveryIds as $deliveryId) {
				$ins->executeStatement([
					'program_id' => $programId,
					'delivery_id' => $deliveryId
				]);
			}

			$conn->commit();
		} catch (\Exception $e) {
			if ($conn->isTransactionActive()) {
				$conn->rollBack();
			}
			throw $e;
		}
	}
}
