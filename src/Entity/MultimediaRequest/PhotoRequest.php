<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\MultimediaRequest\MultimediaRequest;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\PhotoRequestRepository")
 */
class PhotoRequest extends MultimediaRequest
{
    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime(message="You must provide a valid starting date and time.")
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime(message="You must provide a valid ending date and time.")
     */
    private $endTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(
     *      max = 20,
     *      maxMessage = "Intended use cannot be longer than {{ limit }} characters"
     * )
     */
    private $intendedUse;

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

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
}
