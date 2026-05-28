<?php
namespace App\Repository\Cas;

use App\Entity\Cas\CasCycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CasCycle|null find($id, $lockMode = null, $lockVersion = null)
 * @method CasCycle|null findOneBy(array $criteria, array $orderBy = null)
 * @method CasCycle[]    findAll()
 * @method CasCycle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CasCycleRepository extends ServiceEntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, CasCycle::class);
	}

	public function findAllOrderedByName(): array
	{
		return $this->createQueryBuilder('c')
			->orderBy('c.cycleName', 'ASC')
			->getQuery()
			->getResult();
	}

	public function findPublicCycles(): array
	{
		return $this->createQueryBuilder('c')
			->where('c.isPublic = :true')
			->setParameter('true', true)
			->orderBy('c.cycleName', 'ASC')
			->getQuery()
			->getResult();
	}

	public function findCurrentCycle(): ?CasCycle
	{
		return $this->findOneBy(['current' => true]);
	}

	/**
	 * Sets the given cycle as current, unsetting all others in a single transaction.
	 */
	public function setCurrentCycle(int $cycleId): void
	{
		$em = $this->getEntityManager();

		$em->wrapInTransaction(function () use ($em, $cycleId) {
			$em->createQueryBuilder()
				->update(CasCycle::class, 'c')
				->set('c.current', ':false')
				->setParameter('false', false)
				->getQuery()
				->execute();

			$em->createQueryBuilder()
				->update(CasCycle::class, 'c')
				->set('c.current', ':true')
				->where('c.id = :id')
				->setParameter('true', true)
				->setParameter('id', $cycleId)
				->getQuery()
				->execute();
		});
	}
}
