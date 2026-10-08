<?php
namespace App\Repository\Ic;

use App\Entity\Ic\IcCollege;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the colleges shared across IC Command apps.
 * @method IcCollege|null find($id, $lockMode = null, $lockVersion = null)
 * @method IcCollege|null findOneBy(array $criteria, array $orderBy = null)
 * @method IcCollege[]    findAll()
 * @method IcCollege[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IcCollegeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcCollege::class);
    }

    /**
     * All colleges as plain rows for the college pickers, sorted by name.
     *
     * @return array<int, array{id: int, college: string, url: ?string}>
     */
    public function findForDropdown(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS id', 'c.college AS college', 'c.url AS url')
            ->orderBy('c.college', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
