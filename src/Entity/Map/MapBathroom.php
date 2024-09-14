<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapBathroomRepository")]
class MapBathroom extends MapItem
{
	const ITEM_TYPE = 'bathroom';

	#[ORM\Column(type: "boolean")]
	#[Assert\NotNull(message: "Is the bathroom gender neutral?")]
	#[SerializedName("isGenderNeutral")]
	#[Groups("bldgs")]
	private $isGenderNeutral;

	#[ORM\ManyToOne(targetEntity: "MapBuilding", inversedBy: "bathrooms")]
	#[ORM\JoinColumn(name: "building_id", referencedColumnName: "id", nullable: true, onDelete: "CASCADE")]
	private $building;

	#[SerializedName("itemType")]
	#[Groups("bldgs")]
	public function getItemType(): string
	{
		return self::ITEM_TYPE;
	}

	public function getIsGenderNeutral(): bool
	{
		return $this->isGenderNeutral;
	}

	public function setIsGenderNeutral(bool $isGenderNeutral): void
	{
		$this->isGenderNeutral = $isGenderNeutral;
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
		if (!$this->building) return null;
		return ['id' => $this->building->getId(), 'name' => $this->building->getName()];
	}
}
