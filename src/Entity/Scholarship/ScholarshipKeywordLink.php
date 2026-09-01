<?php
namespace App\Entity\Scholarship;

use App\Repository\Scholarship\ScholarshipKeywordLinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Join table linking a scholarship to a managed keyword. Composite primary key of two
 * real ManyToOne associations (identifier-through-association), both with a database-level
 * FK using ON DELETE CASCADE — same clean pattern as ScholarshipProgram.
 */
#[ORM\Entity(repositoryClass: ScholarshipKeywordLinkRepository::class)]
#[ORM\Table(name: 'scholarships_scholarship_keyword')]
class ScholarshipKeywordLink
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Scholarship::class, inversedBy: 'keywordLinks')]
    #[ORM\JoinColumn(name: 'scholarship_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Scholarship $scholarship = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ScholarshipKeyword::class, inversedBy: 'scholarshipLinks')]
    #[ORM\JoinColumn(name: 'keyword_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ScholarshipKeyword $keyword = null;

    public function getScholarship(): ?Scholarship
    {
        return $this->scholarship;
    }

    public function setScholarship(?Scholarship $scholarship): self
    {
        $this->scholarship = $scholarship;
        return $this;
    }

    public function getKeyword(): ?ScholarshipKeyword
    {
        return $this->keyword;
    }

    public function setKeyword(?ScholarshipKeyword $keyword): self
    {
        $this->keyword = $keyword;
        return $this;
    }
}
