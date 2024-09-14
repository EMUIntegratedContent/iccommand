<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapServiceTypeRepository")]
class MapServiceType
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("bldgs")]
	private $id;

	#[ORM\Column(type: "string", length: 255)]
	#[Assert\NotBlank(message: "You must provide a name for this service.")]
	#[Groups("bldgs")]
	private $name;

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
