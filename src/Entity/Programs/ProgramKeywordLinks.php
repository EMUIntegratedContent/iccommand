<?php

namespace App\Entity\Programs;

use App\Repository\Programs\ProgramKeywordsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramKeywordsRepository::class)]
#[ORM\Table(name: 'program_keyword_links', schema: 'programs')]
class ProgramKeywordLinks
{
    #[ORM\Column]
    #[ORM\Id]
    private ?int $program_id = null;

    #[ORM\Column]
    #[ORM\Id]
    private ?int $keyword_id = null;

    #[ORM\ManyToOne(targetEntity: ProgramKeywords::class, inversedBy: 'programKeywordLinks')]
    #[ORM\JoinColumn(name: 'keyword_id', referencedColumnName: 'id')]
    private ?ProgramKeywords $keyword = null;

    public function getProgramId(): ?int
    {
        return $this->program_id;
    }

    public function setProgramId(int $program_id): static
    {
        $this->program_id = $program_id;

        return $this;
    }

    public function getKeywordId(): ?int
    {
        return $this->keyword_id;
    }

    public function setKeywordId(int $keyword_id): static
    {
        $this->keyword_id = $keyword_id;

        return $this;
    }
}


