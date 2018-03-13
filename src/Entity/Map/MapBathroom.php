<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Map\MapBuilding;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapBathroomRepository")
 * @Serializer\VirtualProperty(
 *     "itemType",
 *     exp="object.getItemType()",
 *     options={@Serializer\SerializedName("itemType")}
 *  )
 */
class MapBathroom extends MapItem
{
    const ITEM_TYPE = 'bathroom';

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull(message="Is the bathroom gender neutral?")
     * @Serializer\SerializedName("isGenderNeutral")
     */
    private $isGenderNeutral;

    /**
     * Many bathrooms belong to one building.
     * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="bathrooms")
     * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $building;

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
