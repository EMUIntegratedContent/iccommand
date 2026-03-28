<?php
namespace App\Repository\GradCas;

use App\Entity\GradCas\GradCasCycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GradCasCycle|null find($id, $lockMode = null, $lockVersion = null)
 * @method GradCasCycle|null findOneBy(array $criteria, array $orderBy = null)
 * @method GradCasCycle[]    findAll()
 * @method GradCasCycle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GradCasCycleRepository extends ServiceEntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, GradCasCycle::class);
	}

	public function findAllOrderedByName(): array
	{
		return $this->createQueryBuilder('c')
			->orderBy('c.cycleName', 'ASC')
			->getQuery()
			->getResult();
	}

	public function findCurrentCycle(): ?GradCasCycle
	{
		return $this->findOneBy(['current' => true]);
	}

	/**
	 * Sets the given cycle as current, unsetting all others in a single transaction.
	 */
	public function setCurrentCycle(int $cycleId): void
	{
		$em = $this->getEntityManager();

		// Unset all cycles
		$em->createQueryBuilder()
			->update(GradCasCycle::class, 'c')
			->set('c.current', ':false')
			->setParameter('false', false)
			->getQuery()
			->execute();

		// Set the specified cycle as current
		$em->createQueryBuilder()
			->update(GradCasCycle::class, 'c')
			->set('c.current', ':true')
			->where('c.id = :id')
			->setParameter('true', true)
			->setParameter('id', $cycleId)
			->getQuery()
			->execute();
	}
}
