<?php

namespace App\Entity\Map;

use App\Entity\Ic\IcAdvisor;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapBuildingRepository")]
#[ORM\Table(name: 'map_building')]
class MapBuilding extends MapItem
{
	const ITEM_TYPE = 'building';

	#[ORM\Column(type: "text", nullable: true)]
	#[Groups("bldgs")]
	private $hours;

	#[ORM\Column(type: "string", nullable: true)]
	#[Groups("bldgs")]
	private $address;

	/**
	 * One building has (zero to) many bathrooms.
	 */
	#[ORM\OneToMany(mappedBy: "building", targetEntity: "MapBathroom", cascade: ["persist", "remove"], orphanRemoval: true)]
	#[Groups("bldgs")]
	private $bathrooms;

	/**
	 * One building has (zero to) many dining options.
	 */
	#[ORM\OneToMany(mappedBy: "building", targetEntity: "MapDining", cascade: ["persist"])]
	#[SerializedName("diningOptions")]
	#[Groups("bldgs")]
	private $diningOptions;

	/**
	 * One building has (zero to) many emergency devices.
	 */
	#[ORM\OneToMany(mappedBy: "building", targetEntity: "MapEmergency", cascade: ["persist"])]
	#[SerializedName("emergencyDevices")]
	#[Groups("bldgs")]
	private $emergencyDevices;

	/**
	 * One building has (zero to) many exhibits.
	 */
	#[ORM\OneToMany(mappedBy: "building", targetEntity: "MapExhibit", cascade: ["persist"])]
	#[Groups("bldgs")]
	private $exhibits;

	/**
	 * One building has (zero to) many services.
	 */
	#[ORM\OneToMany(mappedBy: "building", targetEntity: "MapService", cascade: ["persist"])]
	#[Groups("bldgs")]
	private $services;

	/**
	 * One building has (zero to) many dispensers.
	 */
	#[ORM\OneToMany(mappedBy: "building", targetEntity: "MapDispenser", cascade: ["persist"])]
	#[Groups("bldgs")]
	private $dispensers;

	/**
	 * Many buildings have one type.
	 */
	#[ORM\ManyToOne(targetEntity: "App\Entity\Map\MapBuildingType")]
	#[ORM\JoinColumn(name: "buildingtype_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
	#[SerializedName("buildingType")]
	#[Groups("bldgs")]
	private $buildingType;

	/**
	 * Many buildings have many advisors. Serialized through the advisorIds/advisorNames/
	 * icAdvisorsInfo virtual properties below rather than as nested objects.
	 *
	 * @var Collection<int, IcAdvisor>
	 */
	#[ORM\ManyToMany(targetEntity: "App\Entity\Ic\IcAdvisor", fetch: "EAGER")]
	#[ORM\JoinTable(name: "map_building_advisor")]
	#[ORM\JoinColumn(name: "building_id", referencedColumnName: "id", onDelete: "CASCADE")]
	#[ORM\InverseJoinColumn(name: "advisor_id", referencedColumnName: "id")]
	private $icAdvisors;


	/**
	 * One building has (zero to) many directory departments.
	 */
	#[ORM\OneToMany(mappedBy: "mapBuilding", targetEntity: "App\Entity\Directory\Department", cascade: ["persist"])]
	private $directoryDepartments;

	public function __construct()
	{
		parent::__construct();
		$this->icAdvisors = new ArrayCollection();
		$this->bathrooms = new ArrayCollection();
		$this->diningOptions = new ArrayCollection();
		$this->emergencyDevices = new ArrayCollection();
		$this->exhibits = new ArrayCollection();
		$this->services = new ArrayCollection();
		$this->dispensers = new ArrayCollection();
		$this->directoryDepartments = new ArrayCollection();
	}

	#[SerializedName("itemType")]
	#[Groups("bldgs")]
	public function getItemType(): string
	{
		return self::ITEM_TYPE;
	}

	public function addBathroom(?MapBathroom $bathroom = null)
	{
		$this->bathrooms[] = $bathroom;
	}

	public function addDiningOption(?MapDining $diningOption = null)
	{
		$this->diningOptions[] = $diningOption;
	}

	public function addEmergencyDevice(?MapEmergency $emergencyDevice = null)
	{
		$this->emergencyDevices[] = $emergencyDevice;
	}

	public function addExhibit(?MapExhibit $exhibit = null)
	{
		$this->exhibits[] = $exhibit;
	}

	public function addService(?MapService $service = null)
	{
		$this->services[] = $service;
	}

	public function addDispenser(?MapDispenser $dispenser = null)
	{
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

	public function setBuildingType(?MapBuildingType $buildingType)
	{
		$this->buildingType = $buildingType;
	}

	/**
	 * @return Collection<int, IcAdvisor>
	 */
	#[Ignore]
	public function getIcAdvisors(): Collection
	{
		return $this->icAdvisors;
	}

	/**
	 * Makes the building's advisors exactly these.
	 *
	 * @param IcAdvisor[] $icAdvisors
	 */
	public function setIcAdvisors(array $icAdvisors): void
	{
		foreach ($this->icAdvisors->toArray() as $existing) {
			if (!in_array($existing, $icAdvisors, true)) {
				$this->icAdvisors->removeElement($existing);
			}
		}
		foreach ($icAdvisors as $icAdvisor) {
			if (!$this->icAdvisors->contains($icAdvisor)) {
				$this->icAdvisors->add($icAdvisor);
			}
		}
	}

	/**
	 * @return int[]
	 */
	#[Groups("bldgs")]
	public function getAdvisorIds(): array
	{
		return array_values($this->icAdvisors->map(fn (IcAdvisor $c) => $c->getId())->toArray());
	}

	/**
	 * @return string[]
	 */
	#[Groups("bldgs")]
	public function getAdvisorNames(): array
	{
		return array_values($this->icAdvisors->map(fn (IcAdvisor $c) => $c->getDisplayName())->toArray());
	}

	/**
	 * What the public feed publishes about the advisors, sorted by name.
	 *
	 * @return array<int, array{name: string, email: ?string, phone: ?string, url: ?string}>
	 */
	#[Groups("bldgs")]
	public function getIcAdvisorsInfo(): array
	{
		$advisors = $this->icAdvisors->toArray();
		usort($advisors, fn (IcAdvisor $a, IcAdvisor $b) => strcasecmp($a->getDisplayName(), $b->getDisplayName()));

		return array_map(fn (IcAdvisor $c) => $c->toPublicArray(), $advisors);
	}

	public function getDirectoryDepartments()
	{
		return $this->directoryDepartments;
	}

	public function addDirectoryDepartment($directoryDepartment)
	{
		$this->directoryDepartments[] = $directoryDepartment;
	}
}
