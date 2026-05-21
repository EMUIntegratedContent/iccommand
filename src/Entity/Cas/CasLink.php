<?php
namespace App\Entity\Cas;

use App\Repository\Cas\CasLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CasLinkRepository::class)]
#[ORM\Table(name: 'cas_link')]
class CasLink {

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("cas")]
	private $id;

	#[ORM\ManyToOne(targetEntity: CasCycle::class, inversedBy: "links")]
	#[ORM\JoinColumn(name: "cycle_id", referencedColumnName: "id", nullable: false)]
	#[Groups("cas")]
	private ?CasCycle $cycle = null;

	/**
	 * Logical FK to programs.program_programs.id (cross-database, no DB constraint).
	 * Nullable for non-program entries (Guest Student, Non-Degree Admission, etc.)
	 */
	#[ORM\Column(type: "integer", nullable: true)]
	#[SerializedName("programId")]
	#[Groups("cas")]
	private ?int $programId = null;

	#[ORM\Column(type: "string", length: 255)]
	#[SerializedName("degreeName")]
	#[Assert\NotBlank(message: "You must provide a degree/program name.")]
	#[Groups("cas")]
	private $degreeName;

	#[ORM\Column(type: "string", length: 500)]
	#[Assert\NotBlank(message: "You must provide an application link.")]
	#[Groups("cas")]
	private $link;

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
	}

	/* ************************** Getters and Setters ************************* */

	public function getId() {
		return $this->id;
	}

	public function getCycle(): ?CasCycle {
		return $this->cycle;
	}

	public function setCycle(?CasCycle $cycle): self {
		$this->cycle = $cycle;
		return $this;
	}

	public function getProgramId(): ?int {
		return $this->programId;
	}

	public function setProgramId(?int $programId): self {
		$this->programId = $programId;
		return $this;
	}

	public function getDegreeName(): ?string {
		return $this->degreeName;
	}

	public function setDegreeName(string $degreeName): self {
		$this->degreeName = $degreeName;
		return $this;
	}

	public function getLink(): ?string {
		return $this->link;
	}

	public function setLink(string $link): self {
		$this->link = $link;
		return $this;
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
