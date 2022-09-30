<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapBusRepository")
 */
class MapBus extends MapItem
{
  const ITEM_TYPE = 'bus';

  /**
   * @SerializedName("itemType")
   * @Groups("bldgs")
   * @return String
  */
  public function getItemType(){
    return constant("self::ITEM_TYPE");
  }
}
