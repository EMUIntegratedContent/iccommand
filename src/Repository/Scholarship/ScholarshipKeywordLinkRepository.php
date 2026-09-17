<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\ScholarshipKeywordLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the scholarship-keyword links.
 * @method ScholarshipKeywordLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScholarshipKeywordLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScholarshipKeywordLink[]    findAll()
 * @method ScholarshipKeywordLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipKeywordLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScholarshipKeywordLink::class);
    }
}
