<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapExhibitTypeRepository")]
class MapExhibitType
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("bldgs")]
	private $id;

	#[ORM\Column(type: "string")]
	#[Assert\NotBlank(message: "You must provide a name for this exhibit.")]
	#[Groups("bldgs")]
	private $name;

	// Getters and setters
	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;
		return $this;
	}

	public function __toString(): string
	{
		return $this->getName();
	}
}
