<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\MultimediaRequest\HeadshotRequestRepository")]
class HeadshotRequest extends MultimediaRequest
{
	const REQUEST_TYPE = 'headshot';

	#[ORM\ManyToOne(targetEntity: "PhotoHeadshotDate", inversedBy: "headshotRequests")]
	#[SerializedName("timeSlot")]
	#[Groups("multi")]
	private $timeSlot;

	#[SerializedName("requestType")]
	#[Groups("multi")]
	public function getRequestType(): string
	{
		return self::REQUEST_TYPE;
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
