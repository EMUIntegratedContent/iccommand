<?php
namespace App\Repository\Ic;

use App\Entity\Ic\IcDepartment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the departments shared across IC Command apps.
 * @method IcDepartment|null find($id, $lockMode = null, $lockVersion = null)
 * @method IcDepartment|null findOneBy(array $criteria, array $orderBy = null)
 * @method IcDepartment[]    findAll()
 * @method IcDepartment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IcDepartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcDepartment::class);
    }

    /**
     * All departments as plain rows for the department pickers, sorted by name.
     *
     * @return array<int, array{id: int, department: string, college_id: int}>
     */
    public function findForDropdown(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.id AS id', 'd.department AS department', 'IDENTITY(d.college) AS college_id')
            ->orderBy('d.department', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
