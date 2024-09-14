<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\MultimediaRequest\MultimediaRequestStatusNoteRepository")]
class MultimediaRequestStatusNote
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("multi")]
	private $id;

	#[ORM\Column(type: "text")]
	#[Assert\NotBlank(message: "You must provide text for this note.")]
	#[Groups("multi")]
	private $note;

	#[ORM\ManyToOne(targetEntity: "MultimediaRequest", inversedBy: "statusNotes")]
	#[ORM\JoinColumn(name: "multimedia_request_id", referencedColumnName: "id", onDelete: "CASCADE")]
	#[SerializedName("multimediaRequest")]
	private $multimediaRequest;

	#[Gedmo\Blameable(on: "create")]
	#[ORM\Column(type: "string", nullable: true)]
	#[Groups("multi")]
	private $createdBy;

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

	public function getId()
	{
		return $this->id;
	}

	public function getNote(): ?string
	{
		return $this->note;
	}

	public function setNote(string $note): self
	{
		$this->note = $note;
		return $this;
	}

	public function getMultimediaRequest(): ?MultimediaRequest
	{
		return $this->multimediaRequest;
	}

	public function setMultimediaRequest(?MultimediaRequest $multimediaRequest): void
	{
		$this->multimediaRequest = $multimediaRequest;
	}

	/** GEDMO FIELDS **/

	public function getCreatedBy(): ?string
	{
		return $this->createdBy;
	}

	public function getCreated(): ?\DateTimeInterface
	{
		return $this->created;
	}

	public function getUpdated(): ?\DateTimeInterface
	{
		return $this->updated;
	}

	public function getContentChanged(): ?\DateTimeInterface
	{
		return $this->contentChanged;
	}
}
