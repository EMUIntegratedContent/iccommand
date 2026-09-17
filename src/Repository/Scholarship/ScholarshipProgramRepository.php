<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\ScholarshipProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the scholarship-program links.
 * @method ScholarshipProgram|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScholarshipProgram|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScholarshipProgram[]    findAll()
 * @method ScholarshipProgram[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScholarshipProgram::class);
    }
}
