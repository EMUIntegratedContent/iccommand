<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

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
   */
   private $type;

   /**
    * Many emergency devices can belong to one building.
    * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="emergencyDevices")
    * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
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
}
