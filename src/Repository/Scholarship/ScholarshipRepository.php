<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\Scholarship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the scholarships.
 * @method Scholarship|null find($id, $lockMode = null, $lockVersion = null)
 * @method Scholarship|null findOneBy(array $criteria, array $orderBy = null)
 * @method Scholarship[]    findAll()
 * @method Scholarship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scholarship::class);
    }
}
