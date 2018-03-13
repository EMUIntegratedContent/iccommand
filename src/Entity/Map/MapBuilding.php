<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapBuildingRepository")
 * @Serializer\VirtualProperty(
 *     "itemType",
 *     exp="object.getItemType()",
 *     options={@Serializer\SerializedName("itemType")}
 *  )
 */
class MapBuilding extends MapItem
{
    const ITEM_TYPE = 'building';

    /**
     * One building has (zero to) many bathrooms.
     * @ORM\OneToMany(targetEntity="MapBathroom", mappedBy="building", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $bathrooms;

    /**
     * One building has (zero to) many emergency devices.
     * @ORM\OneToMany(targetEntity="MapEmergency", mappedBy="building", cascade={"persist", "remove"})
     * @Serializer\SerializedName("emergencyDevices")
     */
    private $emergencyDevices;

    /**
     * One building has (zero to) many exhibits.
     * @ORM\OneToMany(targetEntity="MapExhibit", mappedBy="building", cascade={"persist", "remove"})
     */
    private $exhibits;

    public function __construct() {
        parent::__construct();
        $this->bathrooms = new ArrayCollection();
        $this->emergencyDevices = new ArrayCollection();
    }

    public function getItemType(){
      return constant("self::ITEM_TYPE");
    }

    public function addBathroom(MapBathroom $bathroom = null){
      $this->bathrooms[] = $bathroom;
    }

    public function addEmergencyDevice(MapEmergency $emergencyDevice = null){
      $this->emergencyDevices[] = $emergencyDevice;
    }

    public function addExhibit(MapExhibit $exhibit = null){
      $this->exhibits[] = $exhibit;
    }

    /**
     * @return Collection|MapBathroom[]
     */
    public function getBathrooms()
    {
        return $this->bathrooms;
    }

    /**
     * @return Collection|MapEmergency[]
     */
    public function getEmergencyDevices()
    {
        return $this->emergencyDevices;
    }

    /**
     * @return Collection|MapExhibit[]
     */
    public function getExhibits()
    {
        return $this->exhibits;
    }
}
