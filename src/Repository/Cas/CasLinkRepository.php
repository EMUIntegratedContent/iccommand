<?php
namespace App\Repository\Cas;

use App\Entity\Cas\CasLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CasLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method CasLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method CasLink[]    findAll()
 * @method CasLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CasLinkRepository extends ServiceEntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, CasLink::class);
	}

	public function paginatedLinks(int $cycleId, int $currentPage, int $pageSize): array
	{
		$offset = ($currentPage - 1) * $pageSize;

		$links = $this->createQueryBuilder('l')
			->where('l.cycle = :cycleId')
			->orderBy('l.degreeName', 'ASC')
			->setFirstResult($offset)
			->setMaxResults($pageSize)
			->setParameter('cycleId', $cycleId)
			->getQuery()
			->getResult();

		$totalLinks = $this->createQueryBuilder('l')
			->select('COUNT(l.id)')
			->where('l.cycle = :cycleId')
			->setParameter('cycleId', $cycleId)
			->getQuery()
			->getSingleScalarResult();

		return [
			'links' => $links,
			'totalRows' => $totalLinks
		];
	}

	public function searchLinks(string $searchTerm, int $cycleId): array
	{
		return $this->createQueryBuilder('l')
			->select('l.degreeName AS degreeName, l.id')
			->where('l.degreeName LIKE :searchTerm')
			->andWhere('l.cycle = :cycleId')
			->orderBy('l.degreeName', 'ASC')
			->setMaxResults(30)
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->setParameter('cycleId', $cycleId)
			->getQuery()
			->getResult();
	}

	public function findByCycle(int $cycleId): array
	{
		return $this->createQueryBuilder('l')
			->where('l.cycle = :cycleId')
			->orderBy('l.degreeName', 'ASC')
			->setParameter('cycleId', $cycleId)
			->getQuery()
			->getResult();
	}

	public function findByProgramId(int $programId): array
	{
		return $this->createQueryBuilder('l')
			->innerJoin('l.cycle', 'c')
			->where('l.programId = :programId')
			->orderBy('c.cycleName', 'DESC')
			->setParameter('programId', $programId)
			->getQuery()
			->getResult();
	}

	public function findByDegreeNameAndCycle(string $degreeName, int $cycleId, ?int $excludeId = null): ?CasLink
	{
		$qb = $this->createQueryBuilder('l')
			->where('l.degreeName = :degreeName')
			->andWhere('l.cycle = :cycleId')
			->setParameter('degreeName', $degreeName)
			->setParameter('cycleId', $cycleId)
			->setMaxResults(1);

		if ($excludeId !== null) {
			$qb->andWhere('l.id != :excludeId')
				->setParameter('excludeId', $excludeId);
		}

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function countByCycle(int $cycleId): int
	{
		return $this->createQueryBuilder('l')
			->select('COUNT(l.id)')
			->where('l.cycle = :cycleId')
			->setParameter('cycleId', $cycleId)
			->getQuery()
			->getSingleScalarResult();
	}
}
