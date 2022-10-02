<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\PhotoRequestRepository")
 */
class PhotoRequest extends MultimediaRequest
{
    const REQUEST_TYPE = 'photo';

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime(message="You must provide a valid starting date and time.")
     * @SerializedName("startTime")
     * @Groups("multi")
     */
    private $startTime;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime(message="You must provide a valid ending date and time.")
     * @SerializedName("endTime")
     * @Groups("multi")
     */
    private $endTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("multi")
     */
    private $location;

    /**
     * Many photo requests can have one type (e.g. "headshot" or "event photo shoot")
     * @ORM\ManyToOne(targetEntity="PhotoRequestType")
     * @SerializedName("photoRequestType")
     * @Groups("multi")
     */
    private $photoRequestType;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(
     *      max = 20,
     *      maxMessage = "Intended use cannot be longer than {{ limit }} characters"
     * )
     * @SerializedName("intendedUse")
     * @Groups("multi")
     */
    private $intendedUse;

    public function __construct() {
        parent::__construct();
    }

    /**
     * @SerializedName("requestType")
     * @Groups("multi")
     * @return String
     */
    public function getRequestType(){
        return constant("self::REQUEST_TYPE");
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getIntendedUse(): ?string
    {
        return $this->intendedUse;
    }

    public function setIntendedUse(?string $intendedUse): self
    {
        $this->intendedUse = $intendedUse;

        return $this;
    }

    public function getPhotoRequestType(): ?PhotoRequestType
    {
        return $this->photoRequestType;
    }

    public function setPhotoRequestType(?PhotoRequestType $photoRequestType): self
    {
        $this->photoRequestType = $photoRequestType;

        return $this;
    }

}
