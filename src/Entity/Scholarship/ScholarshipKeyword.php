<?php
namespace App\Entity\Scholarship;

use App\Repository\Scholarship\ScholarshipKeywordRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A managed keyword that can be attached to many scholarships (M2M via
 * ScholarshipKeywordLink). Names are unique so create/CSV can rely on DB-level dedupe.
 */
#[ORM\Entity(repositoryClass: ScholarshipKeywordRepository::class)]
#[ORM\Table(name: 'scholarships_keyword')]
#[ORM\UniqueConstraint(name: 'UNIQ_scholarships_keyword_name', columns: ['keyword'])]
class ScholarshipKeyword
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("scholarship_keyword")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups("scholarship_keyword")]
    private ?string $keyword = null;

    /**
     * The links to scholarships. No serialization group — never serialized (avoids
     * recursion); the scholarship side exposes keywords via a virtual array getter.
     * @var Collection<int, ScholarshipKeywordLink>
     */
    #[ORM\OneToMany(targetEntity: ScholarshipKeywordLink::class, mappedBy: 'keyword', cascade: ['remove'], orphanRemoval: true)]
    private Collection $scholarshipLinks;

    public function __construct()
    {
        $this->scholarshipLinks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): self
    {
        $this->keyword = $keyword;
        return $this;
    }

    /**
     * @return Collection<int, ScholarshipKeywordLink>
     */
    public function getScholarshipLinks(): Collection
    {
        return $this->scholarshipLinks;
    }
}
