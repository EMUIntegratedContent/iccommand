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
	private int $id;

	#[ORM\Column(length: 255, unique: true)]
	private ?string $program = null;

	#[ORM\ManyToOne(targetEntity: Programs::class)]
	#[ORM\JoinColumn(name: 'program_id', referencedColumnName: 'id', unique: true, nullable: true)]
	private ?Programs $programRef = null;

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

	public function getProgramRef(): ?Programs{
		return $this->programRef;
	}

	public function setProgramRef(?Programs $programRef): static{
		$this->programRef = $programRef;

		return $this;
	}

	/** Convenience method to get the linked program's ID. */
	public function getProgramId(): ?int{
		return $this->programRef?->getId();
	}

	public function getUrl(): ?string{
		return $this->url;
	}

	public function setUrl(string $url): static{
		$this->url = $url;

		return $this;
	}
}
