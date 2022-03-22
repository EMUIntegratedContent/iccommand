<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Document;
use App\Entity\Map\MapitemImage;

/**
* @ORM\Entity(repositoryClass="App\Repository\Map\MapItemRepository")
* @UniqueEntity(
*     fields={"alias"},
*     message="Another map item already uses this alias"
* )
* @ORM\InheritanceType("JOINED")
* @ORM\DiscriminatorColumn(name="discr", type="string")
* @ORM\DiscriminatorMap({"item" = "MapItem", "bathroom" = "MapBathroom", "building" = "MapBuilding", "bus" = "MapBus", "dining" = "MapDining", "emergency" = "MapEmergency", "exhibit" = "MapExhibit", "parking" = "MapParking", "service" = "MapService", "dispenser" = "MapDispenser"})
* @Serializer\XmlRoot("mapItem")
* @Hateoas\Relation("self", href = "expr('/api/mapitems/' ~ object.getId())")
*/
abstract class MapItem
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    * @Serializer\XmlAttribute
    */
    private $id;

    /**
    * @ORM\Column(type="string")
    * @Assert\NotBlank(message="You must provide a name for this item.")
    */
    private $name;

    /**
    * @Gedmo\Slug(fields={"name"})
    * @ORM\Column(length=128, unique=true)
    */
    private $slug;

    /**
    * @ORM\Column(type="string", unique=true, nullable=true)
    */
    private $alias;

    /**
    * @ORM\Column(type="text", nullable=true)
    */
    private $description;

    /**
    * @ORM\Column(type="decimal", precision=10, scale=7, nullable=true)
    * @Serializer\SerializedName("latitudeIllustration")
    */
    private $latitudeIllustration;

    /**
    * @ORM\Column(type="decimal", precision=10, scale=7, nullable=true)
    * @Serializer\SerializedName("longitudeIllustration")
    */
    private $longitudeIllustration;

    /**
    * @ORM\Column(type="decimal", precision=10, scale=7, nullable=true)
    * @Serializer\SerializedName("latitudeSatellite")
    */
    private $latitudeSatellite;

    /**
    * @ORM\Column(type="decimal", precision=10, scale=7, nullable=true)
    * @Serializer\SerializedName("longitudeSatellite")
    */
    private $longitudeSatellite;

    /**
    * Many MapItems have Many Images.
    * @ORM\ManyToMany(targetEntity="App\Entity\Map\MapitemImage", cascade={"remove", "persist"})
    * @ORM\JoinTable(name="mapitems_images",
    *      joinColumns={@ORM\JoinColumn(name="mapitem_id", referencedColumnName="id", onDelete="CASCADE")},
    *      inverseJoinColumns={@ORM\JoinColumn(name="image_id", referencedColumnName="id", onDelete="CASCADE")}
    *      )
    * @ORM\OrderBy({"priority" = "ASC"})
    */
    private $images;

    /**
    * @ORM\Column(type="integer")
    *
    * @Serializer\SerializedName("admissionsTour")
    */
    private $admissionsTour;

    /**
    * @Gedmo\Timestampable(on="create")
    * @ORM\Column(type="datetime")
    */
    private $created;

    /**
    * @Gedmo\Timestampable(on="update")
    * @ORM\Column(type="datetime")
    */
    private $updated;

    /**
    * @ORM\Column(type="datetime", nullable=true)
    * @Gedmo\Timestampable(on="change", field={"title", "body"})
    */
    private $contentChanged;

    public function __construct(){
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

    public function addImage(MapitemImage $image = null){
        $this->images[] = $image;
    }

    /** GEDMO FIELDS **/
    public function getCreated(){
        return $this->created;
    }

    public function getUpdated(){
        return $this->updated;
    }

    public function getContentChanged(){
        return $this->contentChanged;
    }

    public function __toString(){
        return $this->getName();
    }
}
