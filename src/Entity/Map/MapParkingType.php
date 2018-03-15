<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Map\MapParking;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapParkingTypeRepository")
 */
class MapParkingType
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank(message="You must provide a name for parking lot type.")
   */
  private $name;

  /**
   * @ORM\ManyToMany(targetEntity="MapParking", mappedBy="parkingTypes")
   */
  private $parkingLots;

  // Getters and setters
  public function getId()
  {
      return $this->id;
  }

  public function getName()
  {
      return $this->name;
  }

  public function setName($name)
  {
      $this->name = $name;
  }

  public function addParkingLot(MapParking $parkingLot)
  {
      if ($this->parkingLots->contains($parkingLot)) {
          return;
      }
      $this->parkingLots->add($parkingLot);
      $parkingLot->addParkingType($this);
  }

  public function removeParkingLot(MapParking $parkingLot)
  {
      if (!$this->parkingLots->contains($parkingLot)) {
          return;
      }
      $this->parkingLots->removeElement($parkingLot);
      $parkingLot->removeParkingType($this);
  }

  public function __toString(){
    return $this->getName();
  }
}
