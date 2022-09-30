<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapExhibitRepository")
 */
class MapExhibit extends MapItem
{
  const ITEM_TYPE = 'exhibit';

  /**
   * Emergency device is of one type
   * @ORM\ManyToOne(targetEntity="MapExhibitType")
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
   public function getItemType(){
     return constant("self::ITEM_TYPE");
   }

   public function getType(): MapExhibitType
   {
       return $this->type;
   }

   public function setType(MapExhibitType $type)
   {
       $this->type = $type;
   }

   public function getBuilding(): ?MapBuilding
   {
       return $this->building;
   }

   public function setBuilding(?MapBuilding $building = null)
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
