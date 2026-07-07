<?php
namespace App\Entity\Scholarship;

use App\Repository\Scholarship\ScholarshipProgramRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Join table linking a scholarship to an academic program (program_programs.id).
 *
 * Composite primary key of (scholarship_id, program_id). The scholarship side is a
 * real Doctrine association used as part of the identifier; the program side is a
 * plain int — program_programs is mapped by the separate `programs` entity manager,
 * so (per the codebase convention) it is referenced as a loose int FK and the
 * relationship is enforced at the database level by the migration.
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
     * The linked program's ID (program_programs.id). Loose int FK.
     * Unsigned to match program_programs.id (int unsigned).
     */
    #[ORM\Id]
    #[ORM\Column(name: 'program_id', type: 'integer', options: ['unsigned' => true])]
    #[Groups("scholarship")]
    private ?int $programId = null;

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

    public function getProgramId(): ?int
    {
        return $this->programId;
    }

    public function setProgramId(int $programId): self
    {
        $this->programId = $programId;
        return $this;
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
