<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Serializer;
use App\Entity\Map\MapBuildingType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapBuildingRepository")
 */
class MapBuilding extends MapItem
{
    const ITEM_TYPE = 'building';

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("bldgs")
     */
    private $hours;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups("bldgs")
     */
    private $address;

    /**
     * One building has (zero to) many bathrooms.
     * @ORM\OneToMany(targetEntity="MapBathroom", mappedBy="building", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Groups("bldgs")
     */
    private $bathrooms;

    /**
     * One building has (zero to) many dining options.
     * @ORM\OneToMany(targetEntity="MapDining", mappedBy="building", cascade={"persist"})
     * @SerializedName("diningOptions")
     * @Groups("bldgs")
     */
    private $diningOptions;

    /**
     * One building has (zero to) many emergency devices.
     * @ORM\OneToMany(targetEntity="MapEmergency", mappedBy="building", cascade={"persist"})
     * @SerializedName("emergencyDevices")
     * @Groups("bldgs")
     */
    private $emergencyDevices;

    /**
     * One building has (zero to) many exhibits.
     * @ORM\OneToMany(targetEntity="MapExhibit", mappedBy="building", cascade={"persist"})
     * @Groups("bldgs")
     */
    private $exhibits;

    /**
     * One building has (zero to) many services.
     * @ORM\OneToMany(targetEntity="MapService", mappedBy="building", cascade={"persist"})
     * @Groups("bldgs")
     */
    private $services;

    /**
     * One building has (zero to) many dispensers.
     * @ORM\OneToMany(targetEntity="MapDispenser", mappedBy="building", cascade={"persist"})
     * @Groups("bldgs")
     */
    private $dispensers;

    /**
     * Many buildings have one type.
     * @ORM\ManyToOne(targetEntity="App\Entity\Map\MapBuildingType")
     * @ORM\JoinColumn(name="buildingtype_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @SerializedName("buildingType")
     * @Groups("bldgs")
     */
    private $buildingType;

    public function __construct() {
        parent::__construct();
        $this->bathrooms = new ArrayCollection();
        $this->diningOptions = new ArrayCollection();
        $this->emergencyDevices = new ArrayCollection();
        $this->exhibits = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->dispensers = new ArrayCollection();
    }

    /**
     * @SerializedName("itemType")
     * @Groups("bldgs")
     * @return String
    */
    public function getItemType(){
      return constant("self::ITEM_TYPE");
    }

    public function addBathroom(MapBathroom $bathroom = null){
      $this->bathrooms[] = $bathroom;
    }

    public function addDiningOption(MapDining $diningOption = null){
      $this->diningOptions[] = $diningOption;
    }

    public function addEmergencyDevice(MapEmergency $emergencyDevice = null){
      $this->emergencyDevices[] = $emergencyDevice;
    }

    public function addExhibit(MapExhibit $exhibit = null){
      $this->exhibits[] = $exhibit;
    }

    public function addService(MapService $service = null){
      $this->services[] = $service;
    }

		public function addDispenser(MapDispenser $dispenser = null){
			$this->dispensers[] = $dispenser;
		}

    public function getHours()
    {
        return $this->hours;
    }

    public function setHours($hours)
    {
        $this->hours = $hours;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function getBathrooms()
    {
        return $this->bathrooms;
    }

    public function getDiningOptions()
    {
        return $this->diningOptions;
    }

    public function getEmergencyDevices()
    {
        return $this->emergencyDevices;
    }

    public function getExhibits()
    {
        return $this->exhibits;
    }

    public function getServices()
    {
        return $this->services;
    }

		public function getDispensers()
		{
			return $this->dispensers;
		}

    public function getBuildingType(): ?MapBuildingType
    {
        return $this->buildingType;
    }

    public function setBuildingType(MapBuildingType $buildingType = null)
    {
        $this->buildingType = $buildingType;
    }
}
