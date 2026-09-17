<?php
namespace App\Entity\Scholarship;

use App\Entity\Programs\Programs;
use App\Repository\Scholarship\ScholarshipProgramRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Join table linking a scholarship to an academic program (program_programs.id).
 *
 * Composite primary key of (scholarship_id, program_id). Both sides are real Doctrine
 * associations used as part of the identifier: program_programs now lives in the same
 * (ic) database and its Programs entity is mapped on the default entity manager, so the
 * program side is a proper ManyToOne with a database-level FK, not a loose int.
 */
#[ORM\Entity(repositoryClass: ScholarshipProgramRepository::class)]
#[ORM\Table(name: 'scholarships_scholarship_program')]
class ScholarshipProgram
{
    /**
     * The scholarship this link belongs to (identifier via `scholarship_id`).
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Scholarship::class, inversedBy: 'programLinks')]
    #[ORM\JoinColumn(name: 'scholarship_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Scholarship $scholarship = null;

    /**
     * The linked program (identifier via `program_id`).
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Programs::class)]
    #[ORM\JoinColumn(name: 'program_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Programs $program = null;

    /**
     * Optional notes about this scholarship-program link.
     */
    #[ORM\Column(name: 'notes', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $notes = null;

    public function getScholarship(): ?Scholarship
    {
        return $this->scholarship;
    }

    public function setScholarship(?Scholarship $scholarship): self
    {
        $this->scholarship = $scholarship;
        return $this;
    }

    /**
     * Convenience accessor for the owning scholarship's ID.
     */
    #[Groups("scholarship")]
    public function getScholarshipId(): ?int
    {
        return $this->scholarship?->getId();
    }

    public function getProgram(): ?Programs
    {
        return $this->program;
    }

    public function setProgram(?Programs $program): self
    {
        $this->program = $program;
        return $this;
    }

    /**
     * Convenience accessor for the linked program's ID. Preserves the `programId` JSON
     * key the API and frontend consume.
     */
    #[Groups("scholarship")]
    public function getProgramId(): ?int
    {
        return $this->program?->getId();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
}
