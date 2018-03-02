<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Document;

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
 */
abstract class MapItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
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
     * @ORM\ManyToMany(targetEntity="App\Entity\Image")
     * @ORM\JoinTable(name="mapitems_images",
     *      joinColumns={@ORM\JoinColumn(name="mapitem_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="image_id", referencedColumnName="id")}
     *      )
     */
    private $images;

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

/*
    public function setImage(Document $image = null)
    {
        $this->image = $image;
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }
*/
}
