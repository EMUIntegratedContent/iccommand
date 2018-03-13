<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapExhibitRepository")
 */
class MapExhibit extends MapItem
{
  /**
   * Emergency device is of one type
   * @ORM\ManyToOne(targetEntity="MapExhibitType")
   * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
   */
   private $type;

   /**
    * Many emergency devices can belong to one building.
    * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="emergencyDevices")
    * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
    */
   private $building;

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

   public function setBuilding(MapBuilding $building)
   {
       $this->building = $building;
   }
}
