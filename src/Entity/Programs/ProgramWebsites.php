<?php

namespace App\Entity\Programs;

use App\Repository\Programs\ProgramWebsitesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramWebsitesRepository::class)]
#[ORM\Table(name: 'program_websites', schema: 'programs')]
class ProgramWebsites{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255, unique: true)]
	private ?string $program = null;

	#[ORM\Column(length: 255)]
	private ?string $url = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getProgram(): ?string{
		return $this->program;
	}

	public function setProgram(string $program): static{
		$this->program = $program;

		return $this;
	}

	public function getUrl(): ?string{
		return $this->url;
	}

	public function setUrl(string $url): static{
		$this->url = $url;

		return $this;
	}
}
