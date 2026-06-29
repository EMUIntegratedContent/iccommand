<?php

namespace App\Repository\Programs;

use App\Entity\Programs\Programs;
use App\Entity\Programs\ProgramKeywordLinks;
use App\Entity\Programs\ProgramKeywords;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<Programs>
 */
class ProgramsRepository extends ServiceEntityRepository
{

	protected ObjectManager $em;

	public function __construct(ManagerRegistry $doctrine)
	{
		parent::__construct($doctrine, Programs::class);
		$this->em = $doctrine->getManager('programs');
	}

	public function getEntityManager(): \Doctrine\ORM\EntityManagerInterface
	{
		return $this->em;
	}

	public function getProgramEntity($id): ?Programs
	{
		return $this->em->find(Programs::class, $id);
	}

	public function findOneByProgramName(string $progName): ?Programs
	{
		return $this->em->createQueryBuilder()
			->select('p')
			->from(Programs::class, 'p')
			->where('p.program = :progName')
			->setParameter('progName', $progName)
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
	}

	public function getProgram($id)
	{
		// Do raw SQL because the JOIN on program_websites doesn't use FK relationship and thus confuses doctrine
		$programSql = "
			SELECT p.*, w.id AS website_id, w.url,
				GROUP_CONCAT(DISTINCT pd.delivery_id) AS delivery_ids,
				GROUP_CONCAT(DISTINCT pkl.keyword_id) AS keyword_ids,
				GROUP_CONCAT(DISTINCT pcl.college_id) AS college_ids,
				GROUP_CONCAT(DISTINCT pid.department_id) AS department_ids
			FROM program_programs p
			LEFT JOIN program_websites w ON p.program = w.program
			LEFT JOIN program_delivery pd ON p.id = pd.program_id
			LEFT JOIN program_keyword_links pkl ON p.id = pkl.program_id
			LEFT JOIN program_college_link pcl ON p.id = pcl.program_id
			LEFT JOIN program_inter_dept pid ON p.id = pid.program_id
			WHERE p.id = :id
			GROUP BY p.id
		";

		return $this->em->getConnection()->executeQuery($programSql, [
			'id' => $id
		])->fetchAssociative();
	}

	public function paginatedPrograms($currentPage, $pageSize, $catalog): array
	{
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Do raw SQL because the JOIN on program_websites doesn't use FK relationship and thus confuses doctrine
		$programSql = "
			SELECT p.*, w.id AS website_id, w.url
			FROM program_programs p
			LEFT JOIN program_websites w ON p.program = w.program
			WHERE p.catalog = :catalog
			ORDER BY p.full_name ASC
			LIMIT $offset, $pageSize
		";

		$programs = $this->em->getConnection()->executeQuery($programSql, [
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


	public function searchResults($searchTerm, $catalog): array
	{
		// Build the query for getting paginated records
		$qb = $this->em->createQueryBuilder();

		$query = $qb->select('p')
			->distinct()
			->from(Programs::class, 'p')
			// join keyword links and keywords without relying on entity associations
			->leftJoin(ProgramKeywordLinks::class, 'pkl', 'WITH', 'p.id = pkl.program_id')
			->leftJoin(ProgramKeywords::class, 'pk', 'WITH', 'pkl.keyword_id = pk.id')
			->where('p.catalog = :catalog')
			->andWhere(
				$qb->expr()->orX(
					$qb->expr()->like('p.full_name', ':searchTerm'),
					$qb->expr()->like('p.program', ':searchTerm'),
					$qb->expr()->like('pk.keyword', ':searchTerm')
				)
			)
			->orderBy('p.full_name', 'ASC')
			->setMaxResults(30)
			->setParameter('catalog', $catalog)
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->getQuery();

		return $query->getResult();
	}
	public function getColleges(): array
	{
		$clgSql = "
			SELECT *
			FROM program_colleges
			ORDER BY college ASC
		";

		return $this->em->getConnection()->executeQuery($clgSql)->fetchAllAssociative();
	}

	public function getDepartments(): array
	{
		$departmentsSql = "
			SELECT id, department
			FROM program_departments
			ORDER BY department ASC
		";

		return $this->em->getConnection()->executeQuery($departmentsSql)->fetchAllAssociative();
	}

	public function getProgTypes(): array
	{
		$typesSql = "
			SELECT *
			FROM program_types
			ORDER BY type ASC
		";

		return $this->em->getConnection()->executeQuery($typesSql)->fetchAllAssociative();
	}

	public function getDegrees(): array
	{
		$degreesSql = "
			SELECT *
			FROM program_degrees
			ORDER BY degree ASC
		";

		return $this->em->getConnection()->executeQuery($degreesSql)->fetchAllAssociative();
	}

	/**
	 * Get all unique catalog IDs (they change every year).
	 * @return array
	 */
	public function getCatalogIds(): array
	{
		$catalogSql = "
			SELECT DISTINCT catalog, catalog_id
			FROM program_programs
			ORDER BY catalog_id ASC
		";

		return $this->em->getConnection()->executeQuery($catalogSql)->fetchAllAssociative();
	}

	/**
	 * Updates the delivery modes for a program using a transaction.
	 * Deletes existing modes and inserts new ones.
	 *
	 * @param int $programId The ID of the program
	 * @param array<int> $deliveryIds Array of delivery mode IDs
	 * @throws \Exception If database operation fails
	 */
	public function updateProgramDeliveryModes(int $programId, array $deliveryIds): void
	{
		try {
			$conn = $this->em->getConnection();
			$conn->beginTransaction();

			// Delete existing delivery modes
			$delSql = 'DELETE FROM program_delivery WHERE program_id = :program_id';
			$conn->executeStatement($delSql, ['program_id' => $programId]);

			// Insert new delivery modes
			$insSql = 'INSERT INTO program_delivery (program_id, delivery_id) VALUES (:program_id, :delivery_id)';
			foreach ($deliveryIds as $deliveryId) {
				$conn->executeStatement($insSql, [
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

	/**
	 * Updates the keywords for a program using a transaction.
	 * Deletes existing keywords and inserts new ones.
	 *
	 * @param int $programId The ID of the program
	 * @param array<int> $keywordIds Array of keyword IDs
	 * @throws \Exception If database operation fails
	 */
	public function updateProgramKeywords(int $programId, array $keywordIds): void
	{
		try {
			$conn = $this->em->getConnection();
			$conn->beginTransaction();

			// Delete existing keywords
			$delSql = 'DELETE FROM program_keyword_links WHERE program_id = :program_id';
			$conn->executeStatement($delSql, ['program_id' => $programId]);

			// Insert new keywords
			$insSql = 'INSERT INTO program_keyword_links (program_id, keyword_id) VALUES (:program_id, :keyword_id)';
			foreach ($keywordIds as $keywordId) {
				$conn->executeStatement($insSql, [
					'program_id' => $programId,
					'keyword_id' => $keywordId
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

	/**
	 * Updates the colleges for a program using a transaction.
	 * Deletes existing college links and inserts new ones.
	 *
	 * @param int $programId The ID of the program
	 * @param array<int> $collegeIds Array of college IDs
	 * @throws \Exception If database operation fails
	 */
	public function updateProgramColleges(int $programId, array $collegeIds): void
	{
		try {
			$conn = $this->em->getConnection();
			$conn->beginTransaction();

			$delSql = 'DELETE FROM program_college_link WHERE program_id = :program_id';
			$conn->executeStatement($delSql, ['program_id' => $programId]);

			$insSql = 'INSERT INTO program_college_link (program_id, college_id) VALUES (:program_id, :college_id)';
			foreach ($collegeIds as $collegeId) {
				$conn->executeStatement($insSql, [
					'program_id' => $programId,
					'college_id' => $collegeId
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

	/**
	 * Updates the departments for a program using a transaction.
	 * Deletes existing department links and inserts new ones.
	 *
	 * @param int $programId The ID of the program
	 * @param array<int> $departmentIds Array of department IDs
	 * @throws \Exception If database operation fails
	 */
	public function updateProgramDepartments(int $programId, array $departmentIds): void
	{
		try {
			$conn = $this->em->getConnection();
			$conn->beginTransaction();

			$delSql = 'DELETE FROM program_inter_dept WHERE program_id = :program_id';
			$conn->executeStatement($delSql, ['program_id' => $programId]);

			$insSql = 'INSERT INTO program_inter_dept (program_id, department_id) VALUES (:program_id, :department_id)';
			foreach ($departmentIds as $departmentId) {
				$conn->executeStatement($insSql, [
					'program_id' => $programId,
					'department_id' => $departmentId
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
