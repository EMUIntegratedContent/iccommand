<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\MultimediaRequest\MultimediaRequestRepository")]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap(['multimediarequest' => 'MultimediaRequest', 'headshotrequest' => 'HeadshotRequest', 'photorequest' => 'PhotoRequest', 'publicationrequest' => 'PublicationRequest', 'videorequest' => 'VideoRequest'])]
#[Hateoas\Relation("self", href: "expr('/api/multimediarequests/' ~ object.getId())")]
abstract class MultimediaRequest
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("multi")]
	private $id;

	#[ORM\Column(type: "string", length: 255)]
	#[SerializedName("firstName")]
	#[Assert\NotBlank(message: "You must provide a first name for the requester.")]
	#[Groups("multi")]
	private $firstName;

	#[ORM\Column(type: "string", length: 255)]
	#[SerializedName("lastName")]
	#[Assert\NotBlank(message: "You must provide a last name for the requester.")]
	#[Groups("multi")]
	private $lastName;

	#[ORM\Column(type: "string", length: 255)]
	#[Assert\Email(message: "You must provide valid email address for the requester.")]
	#[Groups("multi")]
	private $email;

	#[ORM\Column(type: "string", length: 255, nullable: true)]
	#[Groups("multi")]
	private $phone;

	#[ORM\Column(type: "string", length: 255, nullable: true)]
	#[Groups("multi")]
	private $department;

	#[ORM\Column(type: "text", nullable: true)]
	#[Groups("multi")]
	private $description;

	/**
	 * Upon public submission, this should be set to 'new' in the POST function
	 */
	#[ORM\ManyToOne(targetEntity: "MultimediaRequestStatus")]
	#[Groups("multi")]
	private $status;

	/**
	 * One request has (zero to) many status notes.
	 */
	#[ORM\OneToMany(targetEntity: "MultimediaRequestStatusNote", mappedBy: "multimediaRequest", cascade: ["persist"])]
	#[ORM\OrderBy(["created" => "DESC"])]
	#[SerializedName("statusNotes")]
	#[Groups("multi")]
	private $statusNotes;

	#[ORM\ManyToOne(targetEntity: "MultimediaRequestAssignee", inversedBy: "multimediaRequests")]
	#[Groups("multi")]
	private $assignee;

	#[Gedmo\Timestampable(on: "create")]
	#[ORM\Column(type: "datetime")]
	#[Groups("multi")]
	private $created;

	#[Gedmo\Timestampable(on: "update")]
	#[ORM\Column(type: "datetime")]
	#[Groups("multi")]
	private $updated;

	#[ORM\Column(type: "datetime", nullable: true)]
	#[Gedmo\Timestampable(on: "change", field: ["title", "body"])]
	#[Groups("multi")]
	private $contentChanged;

	public function __construct()
	{
		$this->statusNotes = new ArrayCollection();
	}

	public function getId()
	{
		return $this->id;
	}

	public function getFirstName(): ?string
	{
		return $this->firstName;
	}

	public function setFirstName(string $firstName): self
	{
		$this->firstName = $firstName;
		return $this;
	}

	public function getLastName(): ?string
	{
		return $this->lastName;
	}

	public function setLastName(string $lastName): self
	{
		$this->lastName = $lastName;
		return $this;
	}

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail(string $email): self
	{
		$this->email = $email;
		return $this;
	}

	public function getPhone(): ?string
	{
		return $this->phone;
	}

	public function setPhone(?string $phone): self
	{
		$this->phone = $phone;
		return $this;
	}

	public function getDepartment(): ?string
	{
		return $this->department;
	}

	public function setDepartment(?string $department): self
	{
		$this->department = $department;
		return $this;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): self
	{
		$this->description = $description;
		return $this;
	}

	public function getStatusNotes(): PersistentCollection
	{
		return $this->statusNotes;
	}

	public function addStatusNote(MultimediaRequestStatusNote $note = null)
	{
		$this->statusNotes[] = $note;
	}

	public function getStatus(): ?MultimediaRequestStatus
	{
		return $this->status;
	}

	public function setStatus(?MultimediaRequestStatus $status): self
	{
		$this->status = $status;
		return $this;
	}

	public function getAssignee(): ?MultimediaRequestAssignee
	{
		return $this->assignee;
	}

	public function setAssignee(?MultimediaRequestAssignee $assignee): self
	{
		if (!$assignee) {
			$this->assignee = null;
		} else {
			$this->assignee = $assignee;
		}
		return $this;
	}

	/** GEDMO FIELDS **/

	public function getCreated()
	{
		return $this->created;
	}

	public function getUpdated()
	{
		return $this->updated;
	}

	public function getContentChanged()
	{
		return $this->contentChanged;
	}
}
