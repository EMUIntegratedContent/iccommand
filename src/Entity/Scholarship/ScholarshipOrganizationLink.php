<?php
namespace App\Entity\Scholarship;

use App\Repository\Scholarship\ScholarshipOrganizationLinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Join table linking a scholarship to a managed organization. Composite primary key of two
 * real ManyToOne associations (identifier-through-association), both with a database-level
 * FK using ON DELETE CASCADE — same clean pattern as ScholarshipProgram.
 */
#[ORM\Entity(repositoryClass: ScholarshipOrganizationLinkRepository::class)]
#[ORM\Table(name: 'scholarships_scholarship_organization')]
class ScholarshipOrganizationLink
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Scholarship::class, inversedBy: 'organizationLinks')]
    #[ORM\JoinColumn(name: 'scholarship_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Scholarship $scholarship = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ScholarshipOrganization::class, inversedBy: 'scholarshipLinks')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ScholarshipOrganization $organization = null;

    public function getScholarship(): ?Scholarship
    {
        return $this->scholarship;
    }

    public function setScholarship(?Scholarship $scholarship): self
    {
        $this->scholarship = $scholarship;
        return $this;
    }

    public function getOrganization(): ?ScholarshipOrganization
    {
        return $this->organization;
    }

    public function setOrganization(?ScholarshipOrganization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }
}
