<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MapItemRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"mapitem" = "MapItem", "mapbathroom" = "MapBathroom", "mapparking" = "MapParking"})
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
     */
    private $name;

    /**
     * @ORM\Column(type="string", precision=10, scale=7)
     */
    private $slug;

    /**
     * @ORM\Column(type="text", precision=10, scale=7)
     */
    private $description;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=7)
     */
    private $latitudeIllustration;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=7)
     */
    private $longitudeIllustration;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=7)
     */
    private $latitudeSatellite;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=7)
     */
    private $longitudeSatellite;

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

}
