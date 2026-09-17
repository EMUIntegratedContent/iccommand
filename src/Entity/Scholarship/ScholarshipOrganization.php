<?php
namespace App\Entity\Scholarship;

use App\Repository\Scholarship\ScholarshipOrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A managed organization (club, fraternity, sorority, etc.) that can be attached to many
 * scholarships (M2M via ScholarshipOrganizationLink). Names are unique so create/CSV can
 * rely on DB-level dedupe.
 */
#[ORM\Entity(repositoryClass: ScholarshipOrganizationRepository::class)]
#[ORM\Table(name: 'scholarships_organization')]
#[ORM\UniqueConstraint(name: 'UNIQ_scholarships_organization_name', columns: ['organization'])]
class ScholarshipOrganization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("scholarship_organization")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups("scholarship_organization")]
    private ?string $organization = null;

    /**
     * The links to scholarships. No serialization group — never serialized (avoids
     * recursion); the scholarship side exposes organizations via a virtual array getter.
     * @var Collection<int, ScholarshipOrganizationLink>
     */
    #[ORM\OneToMany(targetEntity: ScholarshipOrganizationLink::class, mappedBy: 'organization', cascade: ['remove'], orphanRemoval: true)]
    private Collection $scholarshipLinks;

    public function __construct()
    {
        $this->scholarshipLinks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return Collection<int, ScholarshipOrganizationLink>
     */
    public function getScholarshipLinks(): Collection
    {
        return $this->scholarshipLinks;
    }
}
