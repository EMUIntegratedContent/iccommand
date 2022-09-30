<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapEmergencyRepository")
 */
class MapEmergency extends MapItem
{
    const ITEM_TYPE = 'emergency device';

    /**
     * Emergency device is of one type
     * @ORM\ManyToOne(targetEntity="MapEmergencyType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * @Groups("bldgs")
     */
    private $type;

    /**
     * Many emergency devices can belong to one building.
     * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="emergencyDevices")
     * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $building;

    /**
     * @SerializedName("itemType")
     * @Groups("bldgs")
     * @return String
     */
    public function getItemType()
    {
        return constant("self::ITEM_TYPE");
    }

    public function getType(): MapEmergencyType
    {
        return $this->type;
    }

    public function setType(MapEmergencyType $type)
    {
        $this->type = $type;
    }

    public function getBuilding(): MapBuilding
    {
        return $this->building;
    }

    public function setBuilding(MapBuilding $building = null)
    {
        $this->building = $building;
    }

    /**
     * Return details about the associated building (avoids a 'circular reference' error when trying to return the actual MapBuilding model)
     * @SerializedName("building")
     * @Groups("bldgs")
     * @return ?array
     */
    public function getBuildingFrontend(): ?array
    {
        if(!$this->building) return null;
        return ['id' => $this->building->getId(), 'name' => $this->building->getName()];
    }
}
