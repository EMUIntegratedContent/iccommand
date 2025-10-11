<?php

namespace App\Repository\CrimeLog;

use App\Entity\CrimeLog\FireLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

class FireLogRepository extends ServiceEntityRepository
{

    protected ObjectManager $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, FireLog::class);
        $this->em = $doctrine->getManager('dps');
    }

	public function deleteById(int $id): void
	{
		// Delete the fire log by id.
		$sql = "DELETE FROM firelog WHERE id = :id";
		$this->em->getConnection()->executeQuery($sql, ['id' => $id]);
	}

    public function findByIncidentNumber(string $incidentNumber): ?FireLog
    {
        return $this->em->getRepository(FireLog::class)->findOneBy(['crnnumber' => $incidentNumber]);
    }
}
