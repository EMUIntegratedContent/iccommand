<?php
namespace App\Entity\Ic;

use App\Repository\Ic\IcDepartmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A department, shared across IC Command apps (Programs, Scholarships). Replaces the
 * per-catalog program_departments rows with one row per department, each belonging to
 * a college.
 */
#[ORM\Entity(repositoryClass: IcDepartmentRepository::class)]
#[ORM\Table(name: 'ic_departments')]
#[ORM\UniqueConstraint(name: 'UNIQ_ic_departments_department', columns: ['department'])]
#[UniqueEntity(fields: ['department'], message: 'That department already exists.')]
class IcDepartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("ic")]
    private ?int $id = null;

    /**
     * Serialized through the collegeId/collegeName virtual properties below rather than as
     * a nested object.
     */
    #[ORM\ManyToOne(targetEntity: IcCollege::class)]
    #[ORM\JoinColumn(name: 'college_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Choose a college.')]
    private ?IcCollege $college = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'A department name is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Department name must be 255 characters or less.')]
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

    public function setCollege(?IcCollege $college): self
    {
        $this->college = $college;
        return $this;
    }

    #[Groups("ic")]
    public function getCollegeId(): ?int
    {
        return $this->college?->getId();
    }

    #[Groups("ic")]
    public function getCollegeName(): ?string
    {
        return $this->college?->getCollege();
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
