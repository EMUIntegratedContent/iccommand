<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\PhotoHeadshotDateRepository")
 */
class PhotoHeadshotDate
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     * @Serializer\SerializedName("dateOfShoot")
     */
    private $dateOfShoot;

    /**
     * @ORM\Column(type="time")
     * @Serializer\SerializedName("startTime")
     */
    private $startTime;

    /**
     * @ORM\Column(type="time")
     * @Serializer\SerializedName("endTime")
     */
    private $endTime;

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

    public function getId()
    {
        return $this->id;
    }

    public function getDateOfShoot(): ?\DateTimeInterface
    {
        return $this->dateOfShoot;
    }

    public function setDateOfShoot(\DateTimeInterface $dateOfShoot): self
    {
        $this->dateOfShoot = $dateOfShoot;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
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
}
