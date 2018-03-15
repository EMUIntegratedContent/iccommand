<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Map\MapBuilding;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapBuildingTypeRepository")
 * @UniqueEntity(
 *    fields={"name"},
 *    errorPath="name",
 *    message="There is already a building type with this name."
 * )
 */
class MapBuildingType
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank(message="You must provide a name for building type.")
   */
  private $name;

  /**
   * @ORM\ManyToMany(targetEntity="MapBuilding", mappedBy="buildingTypes")
   */
  private $buildings;

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

  public function addBuilding(MapBuilding $building)
  {
      if ($this->buildings->contains($building)) {
          return;
      }
      $this->buildings->add($building);
      $building->addBuildingType($this);
  }

  public function removeBuilding(MapBuilding $building)
  {
      if (!$this->buildings->contains($building)) {
          return;
      }
      $this->buildings->removeElement($building);
      $building->removeBuildingType($this);
  }

  public function __toString(){
    return $this->getName();
  }
}
