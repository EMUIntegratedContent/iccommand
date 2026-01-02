<?php

namespace App\Repository\Emergency;

use App\Entity\Emergency\EmergencyBanner;
use App\Entity\Emergency\EmergencyNotice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @extends ServiceEntityRepository<EmergencyBanner>
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
     * Gets the emergency banner record with username populated.
     * @return EmergencyBanner|null The emergency banner record.
     */
    public function findOneBannerWithUsername(): ?EmergencyBanner
    {
        $banner = $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($banner) {
            // Fetch the username for the updated_by user
            $userRepository = $this->getEntityManager()->getRepository(User::class);
            $user = $userRepository->find($banner->getUpdatedBy());
            if ($user) {
                $banner->setUpdatedByUsername($user->getUsername());
            }

            // Fetch all emergency notices
            $noticeRepository = $this->getEntityManager()->getRepository(EmergencyNotice::class);
            $notices = $noticeRepository->findAll();

            // Add notices to banner (we'll need to add a property for this)
            $banner->setNotices($notices);
        }

        return $banner;
    }
}
