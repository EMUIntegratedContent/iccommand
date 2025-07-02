<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapParkingRepository")]
class MapParking extends MapItem
{
	const ITEM_TYPE = 'parking';

	#[ORM\Column(type: "text", nullable: true)]
	#[Groups("bldgs")]
	private $hours;

	#[ORM\Column(type: "integer", nullable: true)]
	#[Groups("bldgs")]
	private $spaces;

	#[ORM\Column(type: "boolean")]
	#[Assert\NotNull(message: "Is the parking lot handicap accessible?")]
	#[SerializedName("hasHandicapSpaces")]
	#[Groups("bldgs")]
	private $hasHandicapSpaces;

	/**
	 * Many Parking lots have Many types.
	 */
	#[ORM\ManyToMany(targetEntity: "App\Entity\Map\MapParkingType")]
	#[JoinTable(name: "mapparking_types")]
	#[JoinColumn(name: "mapparking_id", referencedColumnName: "id")]
	#[InverseJoinColumn(name: "type_id", referencedColumnName: "id")]
	#[ORM\OrderBy(["name" => "ASC"])]
	#[SerializedName("parkingTypes")]
	#[Groups("bldgs")]
	private $parkingTypes;

	public function __construct()
	{
		$this->parkingTypes = new ArrayCollection();
	}

	#[SerializedName("itemType")]
	#[Groups("bldgs")]
	public function getItemType(): string
	{
		return self::ITEM_TYPE;
	}

	public function getHours(): ?string
	{
		return $this->hours;
	}

	public function setHours(?string $hours): void
	{
		$this->hours = $hours;
	}

	public function getSpaces(): ?int
	{
		return $this->spaces;
	}

	public function setSpaces(?int $spaces): void
	{
		$this->spaces = $spaces;
	}

	public function getHasHandicapSpaces(): ?bool
	{
		return $this->hasHandicapSpaces;
	}

	public function setHasHandicapSpaces(?bool $hasHandicapSpaces): void
	{
		$this->hasHandicapSpaces = $hasHandicapSpaces;
	}

	public function getParkingTypes()
	{
		return $this->parkingTypes;
	}

	public function addParkingType(MapParkingType $parkingType): void
	{
		if ($this->parkingTypes->contains($parkingType)) {
			return;
		}
		$this->parkingTypes->add($parkingType);
		$parkingType->addParkingLot($this);
	}

	public function removeParkingType(MapParkingType $parkingType): void
	{
		if (!$this->parkingTypes->contains($parkingType)) {
			return;
		}
		$this->parkingTypes->removeElement($parkingType);
		$parkingType->removeParkingLot($this);
	}
}
