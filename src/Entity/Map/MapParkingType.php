<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapParkingTypeRepository")]
class MapParkingType
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("bldgs")]
	private $id;

	#[ORM\Column(type: "string")]
	#[Assert\NotBlank(message: "You must provide a name for parking lot type.")]
	#[Groups("bldgs")]
	private $name;

	#[ORM\ManyToMany(targetEntity: "MapParking", mappedBy: "parkingTypes")]
	private $parkingLots;

	// Getters and setters
	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function addParkingLot(MapParking $parkingLot): void
	{
		if ($this->parkingLots->contains($parkingLot)) {
			return;
		}
		$this->parkingLots->add($parkingLot);
		$parkingLot->addParkingType($this);
	}

	public function removeParkingLot(MapParking $parkingLot): void
	{
		if (!$this->parkingLots->contains($parkingLot)) {
			return;
		}
		$this->parkingLots->removeElement($parkingLot);
		$parkingLot->removeParkingType($this);
	}

	public function __toString(): string
	{
		return $this->getName();
	}
}
