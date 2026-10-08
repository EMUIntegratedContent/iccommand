<?php
namespace App\Entity\Ic;

use App\Repository\Ic\IcDepartmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A department, shared across IC Command apps (Programs, Scholarships). Replaces the
 * per-catalog program_departments rows with one row per department, each belonging to
 * a college.
 */
#[ORM\Entity(repositoryClass: IcDepartmentRepository::class)]
#[ORM\Table(name: 'ic_departments')]
#[ORM\UniqueConstraint(name: 'UNIQ_ic_departments_department', columns: ['department'])]
class IcDepartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("ic")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: IcCollege::class)]
    #[ORM\JoinColumn(name: 'college_id', referencedColumnName: 'id', nullable: false)]
    #[Groups("ic")]
    private ?IcCollege $college = null;

    #[ORM\Column(length: 255)]
    #[Groups("ic")]
    private ?string $department = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollege(): ?IcCollege
    {
        return $this->college;
    }

    public function setCollege(IcCollege $college): self
    {
        $this->college = $college;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(string $department): self
    {
        $this->department = $department;
        return $this;
    }
}
