<?php

namespace App\Repository\CrimeLog;

use App\Entity\CrimeLog\CrimeLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<CrimeLogRespository>
 */
class CrimeLogRepository extends ServiceEntityRepository
{

    protected ObjectManager $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, CrimeLog::class);
        $this->em = $doctrine->getManager('dps');
    }
}
