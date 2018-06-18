<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\MultimediaRequest\PhotoHeadshotDate;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\HeadshotRequestRepository")
 */
class HeadshotRequest extends MultimediaRequest
{
    const REQUEST_TYPE = 'headshot';

    /**
     * @ORM\ManyToOne(targetEntity="PhotoHeadshotDate", inversedBy="headshotRequests")
     * @Serializer\SerializedName("timeSlot")
     */
    private $timeSlot;

    public function getRequestType(){
        return constant("self::REQUEST_TYPE");
    }

    public function getTimeSlot(): ?PhotoHeadshotDate
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(?PhotoHeadshotDate $headshotDate): self
    {
        $this->timeSlot = $headshotDate;

        return $this;
    }
}
