<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MapBathroomRepository")
 */
class MapBathroom extends MapItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isGenderNeutral;

    public function getIsGenderNeutral(){
      return $this->isGenderNeutral;
    }

    public function setIsGenderNeutral($isGenderNeutral){
      $this->isGenderNeutral = $isGenderNeutral;
    }
}
