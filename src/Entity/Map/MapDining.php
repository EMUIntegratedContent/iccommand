<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapDiningRepository")]
class MapDining extends MapItem
{
	const ITEM_TYPE = 'dining';

	#[ORM\Column(type: "text", nullable: true)]
	#[Groups("bldgs")]
	private $hours;

	#[ORM\ManyToOne(targetEntity: "MapBuilding", inversedBy: "diningOptions")]
	#[ORM\JoinColumn(name: "building_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
	private $building;

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

	public function getBuilding(): ?MapBuilding
	{
		return $this->building;
	}

	public function setBuilding(?MapBuilding $building): void
	{
		$this->building = $building;
	}

	#[SerializedName("building")]
	#[Groups("bldgs")]
	public function getBuildingFrontend(): ?array
	{
		if (!$this->building) {
			return null;
		}
		return ['id' => $this->building->getId(), 'name' => $this->building->getName()];
	}
}
