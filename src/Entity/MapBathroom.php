<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MapBathroomRepository")
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

    public function getItemType(){
      return constant("self::ITEM_TYPE");
    }

    public function getIsGenderNeutral(){
      return $this->isGenderNeutral;
    }

    public function setIsGenderNeutral($isGenderNeutral){
      $this->isGenderNeutral = $isGenderNeutral;
    }
}
