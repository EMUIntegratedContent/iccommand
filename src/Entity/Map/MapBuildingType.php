<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapBuildingTypeRepository")]
#[UniqueEntity(fields: ["name"], message: "There is already a building type with this name.", errorPath: "name")]
class MapBuildingType
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("bldgs")]
	private $id;

	#[ORM\Column(type: "string")]
	#[Assert\NotBlank(message: "You must provide a name for building type.")]
	#[Groups("bldgs")]
	private $name;

	// Getters and setters
	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function __toString(): string
	{
		return $this->getName();
	}
}
