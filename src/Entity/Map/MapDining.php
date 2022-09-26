<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapDiningRepository")
 */
class MapDining extends MapItem
{
  const ITEM_TYPE = 'dining';

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("bldgs")
   */
  private $hours;

  /**
   * Many emergency devices can belong to one building.
   * @ORM\ManyToOne(targetEntity="MapBuilding", inversedBy="diningOptions")
   * @ORM\JoinColumn(name="building_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
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

  public function getHours()
  {
      return $this->hours;
  }

  public function setHours($hours)
  {
      $this->hours = $hours;
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
