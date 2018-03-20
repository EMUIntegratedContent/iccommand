<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Map\MapBuildingType;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapBuildingRepository")
 */
class MapBuilding extends MapItem
{
    const ITEM_TYPE = 'building';

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $hours;

    /**
     * One building has (zero to) many bathrooms.
     * @ORM\OneToMany(targetEntity="MapBathroom", mappedBy="building", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $bathrooms;

    /**
     * One building has (zero to) many emergency devices.
     * @ORM\OneToMany(targetEntity="MapEmergency", mappedBy="building", cascade={"persist"})
     * @Serializer\SerializedName("emergencyDevices")
     */
    private $emergencyDevices;

    /**
     * One building has (zero to) many exhibits.
     * @ORM\OneToMany(targetEntity="MapExhibit", mappedBy="building", cascade={"persist"})
     */
    private $exhibits;

    /**
     * Many MapItems have Many types.
     * @ORM\ManyToMany(targetEntity="App\Entity\Map\MapBuildingType", cascade={"remove", "persist"})
     * @ORM\JoinTable(name="mapbuilding_types",
     *      joinColumns={@ORM\JoinColumn(name="mapbuilding_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="type_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     * @ORM\OrderBy({"name" = "ASC"})
     * @Serializer\SerializedName("buildingTypes")
     */
    private $buildingTypes;

    public function __construct() {
        parent::__construct();
        $this->bathrooms = new ArrayCollection();
        $this->emergencyDevices = new ArrayCollection();
        $this->buildingTypes = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("itemType")
     * @return String
    */
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

    public function getHours()
    {
        return $this->hours;
    }

    public function setHours($hours)
    {
        $this->hours = $hours;
    }

    public function getBathrooms()
    {
        return $this->bathrooms;
    }

    public function getEmergencyDevices()
    {
        return $this->emergencyDevices;
    }

    public function getExhibits()
    {
        return $this->exhibits;
    }

    public function getBuildingTypes()
    {
        return $this->buildingTypes;
    }

    public function addBuildingType(MapBuildingType $buildingType)
    {
        if ($this->buildingTypes->contains($buildingType)) {
            return;
        }
        $this->buildingTypes->add($buildingType);
        $buildingType->addBuilding($this);
    }

    public function removeBuildingType(MapBuildingType $buildingType)
    {
        if (!$this->buildingTypes->contains($buildingType)) {
            return;
        }
        $this->buildingTypes->removeElement($buildingType);
        $buildingType->removeBuilding($this);
    }
}
