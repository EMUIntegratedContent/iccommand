<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\MultimediaRequest\PhotoHeadshotDateRepository")]
class PhotoHeadshotDate{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("multi")]
	private $id;

	#[ORM\Column(type: "date")]
	#[SerializedName("dateOfShoot")]
	#[Groups("multi")]
	private $dateOfShoot;

	#[ORM\Column(type: "time")]
	#[SerializedName("startTime")]
	#[Groups("multi")]
	private $startTime;

	#[ORM\Column(type: "time")]
	#[SerializedName("endTime")]
	#[Groups("multi")]
	private $endTime;

	#[ORM\OneToMany(targetEntity: "HeadshotRequest", mappedBy: "timeSlot")]
	private $headshotRequests;

	#[Gedmo\Timestampable(on: "create")]
	#[ORM\Column(type: "datetime")]
	private $created;

	#[Gedmo\Timestampable(on: "update")]
	#[ORM\Column(type: "datetime")]
	private $updated;

	#[ORM\Column(type: "datetime", nullable: true)]
	#[Gedmo\Timestampable(on: "change", field: ["title", "body"])]
	private $contentChanged;

	public function __construct(){
		$this->headshotRequests = new ArrayCollection();
	}

	public function getId(){
		return $this->id;
	}

	public function getDateOfShoot(): ?\DateTimeInterface{
		return $this->dateOfShoot;
	}

	public function setDateOfShoot(\DateTimeInterface $dateOfShoot): self{
		$this->dateOfShoot = $dateOfShoot;
		return $this;
	}

	public function getStartTime(): ?\DateTimeInterface{
		return $this->startTime;
	}

	public function setStartTime(\DateTimeInterface $startTime): self{
		$this->startTime = $startTime;
		return $this;
	}

	public function getEndTime(): ?\DateTimeInterface{
		return $this->endTime;
	}

	public function setEndTime(\DateTimeInterface $endTime): self{
		$this->endTime = $endTime;
		return $this;
	}

	/**
	 * @return Collection|HeadshotRequest[]
	 */
	public function getHeadshotRequests(): Collection{
		return $this->headshotRequests;
	}

	public function addHeadshotRequest(HeadshotRequest $headshotRequest): self{
		if(!$this->headshotRequests->contains($headshotRequest)){
			$this->headshotRequests[] = $headshotRequest;
			$headshotRequest->setTimeSlot($this);
		}

		return $this;
	}

	public function removeHeadshotRequest(HeadshotRequest $headshotRequest): self{
		if($this->headshotRequests->contains($headshotRequest)){
			$this->headshotRequests->removeElement($headshotRequest);
// set the owning side to null (unless already changed)
			if($headshotRequest->getTimeSlot() === $this){
				$headshotRequest->setTimeSlot(null);
			}
		}

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
