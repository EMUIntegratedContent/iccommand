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
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"mapitem" = "MapItem", "mapbathroom" = "MapBathroom", "mapparking" = "MapParking"})
 * @UniqueEntity(
 *    fields={"name", "slug"},
 *    errorPath="slug",
 *    message="There is already a map item with this slug."
 * )
 * @Hateoas\Relation("self", href = "expr('/api/mapitems/' ~ object.getId())")
 * @Gedmo\SoftDeleteable(fieldName="deleted", timeAware=false, hardDelete=true)
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
     * @ORM\Column(type="string", precision=10, scale=7)
     *
     * @Assert\NotBlank(message="You must provide a name for this item.")
     */
    private $name;

    /**
     * @ORM\Column(type="string", precision=10, scale=7)
     *
     * @Assert\NotBlank(message="You must provide a slug for this item.")
     */
    private $slug;

    /**
     * @ORM\Column(type="text", precision=10, scale=7)
     *
     * @Assert\NotBlank(message="You must provide a description for this item.")
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

    /**
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     */
    private $deleted;

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

    public function setSlug($slug)
    {
        $this->slug = $slug;
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

    public function getDeleted(){
        return $this->deleted;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }
}
