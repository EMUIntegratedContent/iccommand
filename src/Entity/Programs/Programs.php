<?php

namespace App\Entity\Programs;

use App\Repository\Programs\ProgramsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramsRepository::class)]
#[ORM\Table(name: 'program_programs', schema: 'programs')]
class Programs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $catalog = null;

    #[ORM\Column]
    private ?int $college_id = null;

    #[ORM\Column]
    private ?int $department_id = null;

    #[ORM\Column(length: 255)]
    private ?string $program = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $full_name = null;

    #[ORM\Column]
    private ?int $type_id = null;

    #[ORM\Column]
    private ?int $degree_id = null;

    #[ORM\Column]
    private ?int $catalog_id = null;

    #[ORM\Column(nullable: true)]
    private ?int $ref_id = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCatalog(): ?string
    {
        return $this->catalog;
    }

    public function setCatalog(string $catalog): static
    {
        $this->catalog = $catalog;

        return $this;
    }

    public function getCollegeId(): ?int
    {
        return $this->college_id;
    }

    public function setCollegeId(int $college_id): static
    {
        $this->college_id = $college_id;

        return $this;
    }

    public function getDepartmentId(): ?int
    {
        return $this->department_id;
    }

    public function setDepartmentId(int $department_id): static
    {
        $this->department_id = $department_id;

        return $this;
    }

    public function getProgram(): ?string
    {
        return $this->program;
    }

    public function setProgram(string $program): static
    {
        $this->program = $program;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    public function setFullName(string $full_name): static
    {
        $this->full_name = $full_name;

        return $this;
    }

    public function getTypeId(): ?int
    {
        return $this->type_id;
    }

    public function setTypeId(int $type_id): static
    {
        $this->type_id = $type_id;

        return $this;
    }

    public function getDegreeId(): ?int
    {
        return $this->degree_id;
    }

    public function setDegreeId(int $degree_id): static
    {
        $this->degree_id = $degree_id;

        return $this;
    }

    public function getCatalogId(): ?int
    {
        return $this->catalog_id;
    }

    public function setCatalogId(int $catalog_id): static
    {
        $this->catalog_id = $catalog_id;

        return $this;
    }

    public function getRefId(): ?int
    {
        return $this->ref_id;
    }

    public function setRefId(?int $ref_id): static
    {
        $this->ref_id = $ref_id;

        return $this;
    }

}
