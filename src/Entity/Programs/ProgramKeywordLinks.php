<?php

namespace App\Entity\Programs;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'program_keyword_links', schema: 'programs')]
class ProgramKeywordLinks
{
    #[ORM\Column]
    private ?int $program_id = null;

    #[ORM\Column]
    private ?int $keyword_id = null;

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


