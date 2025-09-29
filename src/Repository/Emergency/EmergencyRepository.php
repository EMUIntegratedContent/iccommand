<?php

namespace App\Repository\Emergency;

use App\Entity\Emergency\EmergencyBanner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<CrimeLogRespository>
 */
class EmergencyRepository extends ServiceEntityRepository
{

    protected ObjectManager $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, EmergencyBanner::class);
        $this->em = $doctrine->getManager();
    }

    /**
     * Gets the emergency banner record.
     * @return EmergencyBanner|null
     */
    public function getBanner()
    {
        // There should only ever be one banner record.
        return $this->createQueryBuilder('e')
            ->where('e.id = 1')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
