<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapEmergencyTypeRepository")]
#[UniqueEntity(fields: ["name"], message: "There is already an emergency device type with this name.", errorPath: "name")]
class MapEmergencyType
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("bldgs")]
	private $id;

	#[ORM\Column(type: "string")]
	#[Assert\NotBlank(message: "You must provide a name for emergency type.")]
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
