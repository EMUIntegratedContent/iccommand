<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapDispenserRepository")]
class MapDispenser extends MapItem
{
	const ITEM_TYPE = 'dispenser';

	#[ORM\ManyToOne(targetEntity: "MapBuilding", inversedBy: "dispensers")]
	#[ORM\JoinColumn(name: "building_id", referencedColumnName: "id", nullable: true, onDelete: "CASCADE")]
	private $building;

	#[SerializedName("itemType")]
	#[Groups("bldgs")]
	public function getItemType(): string
	{
		return self::ITEM_TYPE;
	}

	public function getBuilding(): ?MapBuilding
	{
		return $this->building;
	}

	public function setBuilding(?MapBuilding $building): void
	{
		$this->building = $building;
	}

	/**
	 * Return details about the associated building (avoids a 'circular reference' error when trying to return the actual MapBuilding model)
	 */
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
