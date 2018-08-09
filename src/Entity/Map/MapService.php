<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapServiceRepository")
 */
class MapService extends MapItem
{
    const ITEM_TYPE = 'service';

    /**
     * Service is of one type
     * @ORM\ManyToOne(targetEntity="MapServiceType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     */
     private $type;

     /**
      * Many services can belong to one building.
      * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="services")
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

     public function getType(): MapServiceType
     {
         return $this->type;
     }

     public function setType(MapServiceType $type)
     {
         $this->type = $type;
     }

     public function getBuilding(): ?MapBuilding
     {
         return $this->building;
     }

     public function setBuilding(MapBuilding $building = null)
     {
         $this->building = $building;
     }
}
