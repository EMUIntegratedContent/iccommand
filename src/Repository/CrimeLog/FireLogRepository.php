<?php

namespace App\Repository\CrimeLog;

use App\Entity\CrimeLog\FireLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<FireLog>
 */
class FireLogRepository extends ServiceEntityRepository
{
    protected ObjectManager $em;

    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, FireLog::class);
        $this->em = $doctrine->getManager('dps');
    }

    public function getEntityManager(): \Doctrine\ORM\EntityManagerInterface
    {
        return $this->em;
    }
}
