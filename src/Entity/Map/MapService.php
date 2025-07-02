<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapServiceRepository")]
class MapService extends MapItem
{
	const ITEM_TYPE = 'service';

	/**
	 * Service is of one type
	 */
	#[ORM\ManyToOne(targetEntity: "MapServiceType")]
	#[ORM\JoinColumn(name: "type_id", referencedColumnName: "id")]
	#[Groups("bldgs")]
	private $type;

	/**
	 * Many services can belong to one building.
	 */
	#[ORM\ManyToOne(targetEntity: "MapBuilding", inversedBy: "services")]
	#[ORM\JoinColumn(name: "building_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
	private $building;

	#[SerializedName("itemType")]
	#[Groups("bldgs")]
	public function getItemType(): string
	{
		return self::ITEM_TYPE;
	}

	public function getType(): ?MapServiceType
	{
		return $this->type;
	}

	public function setType(?MapServiceType $type): void
	{
		$this->type = $type;
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
