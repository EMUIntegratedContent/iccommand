<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapDispenserRepository")
 */
class MapDispenser extends MapItem
{
	const ITEM_TYPE = 'dispenser';

	/**
	 * Many emergency devices can belong to one building.
	 * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="dispensers")
	 * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
	 */
	private $building;

	/**
	 * @Serializer\VirtualProperty
	 * @Serializer\SerializedName("itemType")
	 * @return String
	 */
	public function getItemType(){
		return constant("self::ITEM_TYPE");
	}

	public function getBuilding(): MapBuilding
	{
		return $this->building;
	}

	public function setBuilding(MapBuilding $building = null)
	{
		$this->building = $building;
	}
}
