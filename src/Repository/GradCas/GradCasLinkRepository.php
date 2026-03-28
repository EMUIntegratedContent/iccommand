<?php
namespace App\Repository\GradCas;

use App\Entity\GradCas\GradCasLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GradCasLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method GradCasLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method GradCasLink[]    findAll()
 * @method GradCasLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GradCasLinkRepository extends ServiceEntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, GradCasLink::class);
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
