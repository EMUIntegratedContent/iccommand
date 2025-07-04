<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\Map\MapItemRepository")]
#[UniqueEntity(fields: ["alias"], message: "Another map item already uses this alias")]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap(["item" => "MapItem", "bathroom" => "MapBathroom", "building" => "MapBuilding", "bus" => "MapBus", "dining" => "MapDining", "emergency" => "MapEmergency", "exhibit" => "MapExhibit", "parking" => "MapParking", "service" => "MapService", "dispenser" => "MapDispenser"])]
#[Hateoas\Relation("self", href: "expr('/api/mapitems/' ~ object.getId())")]
abstract class MapItem
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("bldgs", "department")]
	private $id;

	#[ORM\Column(type: "string")]
	#[Assert\NotBlank(message: "You must provide a name for this item.")]
	#[Groups("bldgs", "department")]
	private $name;

	#[Gedmo\Slug(fields: ["name"])]
	#[ORM\Column(length: 128, unique: true)]
	#[Groups("bldgs")]
	private $slug;

	#[ORM\Column(type: "string", unique: true, nullable: true)]
	#[Groups("bldgs")]
	private $alias;

	#[ORM\Column(type: "text", nullable: true)]
	#[Groups("bldgs")]
	private $description;

	#[ORM\Column(type: "decimal", precision: 10, scale: 7, nullable: true)]
	#[SerializedName("latitudeIllustration")]
	#[Groups("bldgs")]
	private $latitudeIllustration;

	#[ORM\Column(type: "decimal", precision: 10, scale: 7, nullable: true)]
	#[SerializedName("longitudeIllustration")]
	#[Groups("bldgs")]
	private $longitudeIllustration;

	#[ORM\Column(type: "decimal", precision: 10, scale: 7, nullable: true)]
	#[SerializedName("latitudeSatellite")]
	#[Groups("bldgs")]
	private $latitudeSatellite;

	#[ORM\Column(type: "decimal", precision: 10, scale: 7, nullable: true)]
	#[SerializedName("longitudeSatellite")]
	#[Groups("bldgs")]
	private $longitudeSatellite;

	#[ORM\ManyToMany(targetEntity: "App\Entity\Map\MapitemImage", cascade: ["remove", "persist"])]
	#[ORM\JoinTable(name: "mapitems_images")]
	#[JoinColumn(name: "mapitem_id", referencedColumnName: "id", onDelete: "CASCADE")]
	#[InverseJoinColumn(name: "image_id", referencedColumnName: "id", onDelete: "CASCADE")]
	#[ORM\OrderBy(["priority" => "ASC"])]
	#[Groups("bldgs")]
	private $images;

	#[ORM\Column(type: "integer")]
	#[SerializedName("admissionsTour")]
	#[Groups("bldgs")]
	private $admissionsTour = 0;

	#[Gedmo\Timestampable(on: "create")]
	#[ORM\Column(type: "datetime")]
	private $created;

	#[Gedmo\Timestampable(on: "update")]
	#[ORM\Column(type: "datetime")]
	private $updated;

	#[ORM\Column(type: "datetime", nullable: true)]
	#[Gedmo\Timestampable(on: "change", field: ["title", "body"])]
	private $contentChanged;

	public function __construct()
	{
		$this->images = new \Doctrine\Common\Collections\ArrayCollection();
	}

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

	public function getSlug()
	{
		return $this->slug;
	}

	public function setAlias($alias)
	{
		$this->alias = $alias;
	}

	public function getAlias()
	{
		return $this->alias;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		$this->description = $description;
	}

	public function getLatitudeIllustration()
	{
		return $this->latitudeIllustration;
	}

	public function setLatitudeIllustration($latitudeIllustration)
	{
		$this->latitudeIllustration = $latitudeIllustration;
	}

	public function getLongitudeIllustration()
	{
		return $this->longitudeIllustration;
	}

	public function setLongitudeIllustration($longitudeIllustration)
	{
		$this->longitudeIllustration = $longitudeIllustration;
	}

	public function getLatitudeSatellite()
	{
		return $this->latitudeSatellite;
	}

	public function setLatitudeSatellite($latitudeSatellite)
	{
		$this->latitudeSatellite = $latitudeSatellite;
	}

	public function getLongitudeSatellite()
	{
		return $this->longitudeSatellite;
	}

	public function setLongitudeStaellite($longitudeSatellite)
	{
		$this->longitudeSatellite = $longitudeSatellite;
	}

	public function getAdmissionsTour()
	{
		return $this->admissionsTour;
	}

	public function setAdmissionsTour($admissionsTour)
	{
		$this->admissionsTour = $admissionsTour;
	}

	public function getImages()
	{
		return $this->images;
	}

	public function addImage(MapitemImage $image = null)
	{
		$this->images[] = $image;
	}

	/** GEDMO FIELDS **/
	public function getCreated()
	{
		return $this->created;
	}

	public function getUpdated()
	{
		return $this->updated;
	}

	public function getContentChanged()
	{
		return $this->contentChanged;
	}

	public function __toString()
	{
		return $this->getName();
	}
}
