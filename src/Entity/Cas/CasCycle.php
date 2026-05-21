<?php
namespace App\Entity\Cas;

use App\Repository\Cas\CasCycleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CasCycleRepository::class)]
#[ORM\Table(name: 'cas_cycle')]
class CasCycle {

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("cas")]
	private $id;

	#[ORM\Column(type: "string", length: 255)]
	#[SerializedName("cycleName")]
	#[Assert\NotBlank(message: "You must provide a cycle name.")]
	#[Groups("cas")]
	private $cycleName;

	#[ORM\Column(type: "boolean")]
	#[Groups("cas")]
	private bool $current = false;

	#[ORM\Column(type: "boolean")]
	#[Groups("cas")]
	private bool $isPublic = false;

	#[ORM\OneToMany(targetEntity: CasLink::class, mappedBy: "cycle", cascade: ["remove"])]
	private Collection $links;

	/* Gedmo Variables */

	#[ORM\Column(type: "datetime")]
	#[Gedmo\Timestampable(on: "create")]
	#[Groups("cas")]
	private $created;

	#[ORM\Column(type: "string")]
	#[Gedmo\Blameable(on: "create")]
	#[SerializedName("createdBy")]
	#[Groups("cas")]
	private $createdBy;

	#[ORM\Column(type: "datetime")]
	#[Gedmo\Timestampable(on: "update")]
	#[Groups("cas")]
	private $updated;

	#[ORM\Column(type: "string")]
	#[Gedmo\Blameable(on: "update")]
	#[SerializedName("updatedBy")]
	#[Groups("cas")]
	private $updatedBy;

	public function __construct() {
		$this->links = new ArrayCollection();
	}

	/* ************************** Getters and Setters ************************* */

	public function getId() {
		return $this->id;
	}

	public function getCycleName(): ?string {
		return $this->cycleName;
	}

	public function setCycleName(string $cycleName): self {
		$this->cycleName = $cycleName;
		return $this;
	}

	public function isCurrent(): bool {
		return $this->current;
	}

	public function setCurrent(bool $current): self {
		$this->current = $current;
		return $this;
	}

	public function isPublic(): bool {
		return $this->isPublic;
	}

	public function setIsPublic(bool $isPublic): self {
		$this->isPublic = $isPublic;
		return $this;
	}

	public function getLinks(): Collection {
		return $this->links;
	}

	/* ***************************** Gedmo Getters **************************** */

	public function getCreated() {
		return $this->created;
	}

	public function getCreatedBy() {
		return $this->createdBy;
	}

	public function getUpdated() {
		return $this->updated;
	}

	public function getUpdatedBy() {
		return $this->updatedBy;
	}
}
