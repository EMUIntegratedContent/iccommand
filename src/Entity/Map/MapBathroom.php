<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Map\MapBuilding;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapBathroomRepository")
 */
class MapBathroom extends MapItem
{
    const ITEM_TYPE = 'bathroom';

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull(message="Is the bathroom gender neutral?")
     * @Serializer\SerializedName("isGenderNeutral")
     * @Groups("bldgs")
     */
    private $isGenderNeutral;

    /**
     * Many bathrooms belong to one building.
     * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="bathrooms")
     * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $building;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("itemType")
     * @Groups("bldgs")
     * @return String
    */
    public function getItemType(){
      return constant("self::ITEM_TYPE");
    }

    public function getIsGenderNeutral(){
      return $this->isGenderNeutral;
    }

    public function setIsGenderNeutral($isGenderNeutral){
      $this->isGenderNeutral = $isGenderNeutral;
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
