<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapExhibitRepository")
 * @Serializer\VirtualProperty(
 *     "itemType",
 *     exp="object.getItemType()",
 *     options={@Serializer\SerializedName("itemType")}
 *  )
 */
class MapExhibit extends MapItem
{
  const ITEM_TYPE = 'exhibit';

  /**
   * Emergency device is of one type
   * @ORM\ManyToOne(targetEntity="MapExhibitType")
   * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
   */
   private $type;

   /**
    * Many emergency devices can belong to one building.
    * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="emergencyDevices")
    * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
    */
   private $building;

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

   public function getBuilding(): MapBuilding
   {
       return $this->building;
   }

   public function setBuilding(MapBuilding $building = null)
   {
       $this->building = $building;
   }
}
