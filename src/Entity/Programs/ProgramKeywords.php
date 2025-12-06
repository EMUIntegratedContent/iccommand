<?php

namespace App\Entity\Programs;

use App\Repository\Programs\ProgramKeywordsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramKeywordsRepository::class)]
#[ORM\Table(name: 'program_keywords', schema: 'programs')]
class ProgramKeywords
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $keyword = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }
}


