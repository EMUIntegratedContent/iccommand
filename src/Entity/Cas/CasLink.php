<?php
namespace App\Entity\GradCas;

use App\Repository\GradCas\GradCasLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GradCasLinkRepository::class)]
#[ORM\Table(name: 'gradcas_link')]
class GradCasLink {

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: "integer")]
	#[Groups("gradcas")]
	private $id;

	#[ORM\ManyToOne(targetEntity: GradCasCycle::class, inversedBy: "links")]
	#[ORM\JoinColumn(name: "cycle_id", referencedColumnName: "id", nullable: false)]
	#[Groups("gradcas")]
	private ?GradCasCycle $cycle = null;

	/**
	 * Logical FK to programs.program_programs.id (cross-database, no DB constraint).
	 * Nullable for non-program entries (Guest Student, Non-Degree Admission, etc.)
	 */
	#[ORM\Column(type: "integer", nullable: true)]
	#[SerializedName("programId")]
	#[Groups("gradcas")]
	private ?int $programId = null;

	#[ORM\Column(type: "string", length: 255)]
	#[SerializedName("degreeName")]
	#[Assert\NotBlank(message: "You must provide a degree/program name.")]
	#[Groups("gradcas")]
	private $degreeName;

	#[ORM\Column(type: "string", length: 500)]
	#[Assert\NotBlank(message: "You must provide an application link.")]
	#[Groups("gradcas")]
	private $link;

	/* Gedmo Variables */

	#[ORM\Column(type: "datetime")]
	#[Gedmo\Timestampable(on: "create")]
	#[Groups("gradcas")]
	private $created;

	#[ORM\Column(type: "string")]
	#[Gedmo\Blameable(on: "create")]
	#[SerializedName("createdBy")]
	#[Groups("gradcas")]
	private $createdBy;

	#[ORM\Column(type: "datetime")]
	#[Gedmo\Timestampable(on: "update")]
	#[Groups("gradcas")]
	private $updated;

	#[ORM\Column(type: "string")]
	#[Gedmo\Blameable(on: "update")]
	#[SerializedName("updatedBy")]
	#[Groups("gradcas")]
	private $updatedBy;

	public function __construct() {
	}

	/* ************************** Getters and Setters ************************* */

	public function getId() {
		return $this->id;
	}

	public function getCycle(): ?GradCasCycle {
		return $this->cycle;
	}

	public function setCycle(?GradCasCycle $cycle): self {
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
