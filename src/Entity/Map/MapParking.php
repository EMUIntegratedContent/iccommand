<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapParkingRepository")
 */
class MapParking extends MapItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $hours;

    /**
     * @ORM\Column(type="integer")
     */
    private $spaces;

    public function getHours(){
      return $this->hours;
    }

    public function setHours($hours){
      $this->hours = $hours;
    }

    public function getSpaces(){
      return $this->spaces;
    }

    public function setSpaces($spaces){
      $this->spaces = $spaces;
    }
}
