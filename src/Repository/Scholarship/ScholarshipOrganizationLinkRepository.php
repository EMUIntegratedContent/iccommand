<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\ScholarshipOrganizationLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the scholarship-organization links.
 * @method ScholarshipOrganizationLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScholarshipOrganizationLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScholarshipOrganizationLink[]    findAll()
 * @method ScholarshipOrganizationLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipOrganizationLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScholarshipOrganizationLink::class);
    }
}
