<?php

namespace App\Repository\Emergency;

use App\Entity\Emergency\EmergencyNotice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmergencyNotice>
 * 
 * @method EmergencyNotice|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmergencyNotice|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmergencyNotice[]    findAll()
 * @method EmergencyNotice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmergencyNoticeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, EmergencyNotice::class);
    }

    /**
     * Gets all emergency notices.
     * @return EmergencyNotice[]
     */
    public function findAll(): array
    {
        $notices = parent::findAll();

        foreach ($notices as $notice) {
            // Fetch the usernames for created_by and updated_by users
            $userRepository = $this->getEntityManager()->getRepository(User::class);

            $createdByUser = $userRepository->find($notice->getCreatedBy());
            if ($createdByUser) {
                $notice->setCreatedByUsername($createdByUser->getUsername());
            }

            $updatedByUser = $userRepository->find($notice->getUpdatedBy());
            if ($updatedByUser) {
                $notice->setUpdatedByUsername($updatedByUser->getUsername());
            }
        }

        return $notices;
    }

    /**
     * Deletes notices that are not in the provided array of IDs.
     * @param array $keepIds Array of notice IDs to keep
     */
    public function removeNotInList(array $keepIds): void
    {
        if (empty($keepIds)) {
            // If no IDs to keep, delete all notices
            $this->createQueryBuilder('n')
                ->delete()
                ->getQuery()
                ->execute();
            return;
        }

        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.id NOT IN (:ids)')
            ->setParameter('ids', $keepIds)
            ->getQuery()
            ->execute();
    }
}
