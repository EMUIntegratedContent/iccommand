<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\MultimediaRequest\PhotoHeadshotDate;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\HeadshotRequestRepository")
 */
class HeadshotRequest extends MultimediaRequest
{
    const REQUEST_TYPE = 'headshot';

    /**
     * @ORM\ManyToOne(targetEntity="PhotoHeadshotDate", inversedBy="headshotRequests")
     * @SerializedName("timeSlot")
     */
    private $timeSlot;

    /**
     * @SerializedName("requestType")
     * @return String
     */
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
