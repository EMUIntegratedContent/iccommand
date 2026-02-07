<?php

namespace App\Repository\CrimeLog;

use App\Entity\CrimeLog\CrimeLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<CrimeLog>
 */
class CrimeLogRepository extends ServiceEntityRepository
{
    protected ObjectManager $em;

    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, CrimeLog::class);
        $this->em = $doctrine->getManager('dps');
    }

    public function getEntityManager(): \Doctrine\ORM\EntityManagerInterface
    {
        return $this->em;
    }

    public function truncate(): void
		{
			// Truncate the dailylog table.
			$this->em->getConnection()->executeStatement('TRUNCATE TABLE dailylog');
		}
}
