<?php
namespace App\Entity\Scholarship;

use App\Repository\Scholarship\ScholarshipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A scholarship offered at EMU. Column names keep the source system's `schlrshp_*`
 * prefix to ease future data import; PHP properties are clean camelCase.
 */
#[ORM\Entity(repositoryClass: ScholarshipRepository::class)]
#[ORM\Table(name: 'scholarships_scholarship')]
class Scholarship
{
    /* *************************** Member Variables *************************** */

    /**
     * The ID of this scholarship.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("scholarship")]
    private ?int $id = null;

    /**
     * The title of this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "You must provide a title for the scholarship.")]
    #[Groups("scholarship")]
    private ?string $title = null;

    /**
     * Whether this scholarship is active.
     */
    #[ORM\Column(name: 'schlrshp_active', type: 'boolean', options: ['default' => 0])]
    #[Groups("scholarship")]
    private bool $active = false;

    /**
     * The minimum GPA required for this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_gpa', type: 'decimal', precision: 3, scale: 2, nullable: true)]
    #[Groups("scholarship")]
    private ?string $gpa = null;

    /**
     * The URL with more information about this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_url', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $url = null;

    /**
     * The description of this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_description', type: 'text', nullable: true)]
    #[Groups("scholarship")]
    private ?string $description = null;

    /**
     * The date applications open for this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_apply_date', type: 'date', nullable: true)]
    #[Groups("scholarship")]
    private ?\DateTimeInterface $applyDate = null;

    /**
     * The date this scholarship expires / applications close.
     */
    #[ORM\Column(name: 'schlrshp_exp_date', type: 'date', nullable: true)]
    #[Groups("scholarship")]
    private ?\DateTimeInterface $expDate = null;

    /**
     * The county eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_county', type: 'string', length: 160, nullable: true)]
    #[Groups("scholarship")]
    private ?string $county = null;

    /**
     * The city eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_city', type: 'string', length: 160, nullable: true)]
    #[Groups("scholarship")]
    private ?string $city = null;

    /**
     * The state eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_state', type: 'string', length: 160, nullable: true)]
    #[Groups("scholarship")]
    private ?string $state = null;

    /**
     * The high school eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_high_school', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $highSchool = null;

    /**
     * The standing / class eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_standing_class', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $standingClass = null;

    /**
     * The enrollment eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_enrollment', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $enrollment = null;

    /**
     * The gender eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_gender', type: 'string', length: 10, nullable: true)]
    #[Groups("scholarship")]
    private ?string $gender = null;

    /**
     * The ethnicity eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_ethnicity', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $ethnicity = null;

    /**
     * The FAFSA eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_fafsa', type: 'string', length: 15, nullable: true)]
    #[Groups("scholarship")]
    private ?string $fafsa = null;

    /**
     * The parent-status eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_is_parent', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $isParent = null;

    /**
     * The bilingual eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_is_bilingual', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $isBilingual = null;

    /**
     * The organizations eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_organizations', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $organizations = null;

    /**
     * The keywords associated with this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_keywords', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $keywords = null;

    /**
     * The housing eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_housing', type: 'string', length: 4, nullable: true)]
    #[Groups("scholarship")]
    private ?string $housing = null;

    /**
     * The application process for this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_app_proc', type: 'text', nullable: true)]
    #[Groups("scholarship")]
    private ?string $appProc = null;

    /**
     * The award amount for this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_amount', type: 'string', length: 255, nullable: true)]
    #[Groups("scholarship")]
    private ?string $amount = null;

    /**
     * The free-text contact information for this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_contact', type: 'text', nullable: true)]
    #[Groups("scholarship")]
    private ?string $contact = null;

    /**
     * Loose FK to a contact record. The contacts relation is deferred to a later phase.
     */
    #[ORM\Column(name: 'schlrshp_contact_id', type: 'integer', nullable: true)]
    #[Groups("scholarship")]
    private ?int $contactId = null;

    /**
     * The links joining this scholarship to academic programs.
     * @var Collection<int, ScholarshipProgram>
     */
    #[ORM\OneToMany(targetEntity: ScholarshipProgram::class, mappedBy: 'scholarship', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups("scholarship")]
    private Collection $programLinks;

    /* Gedmo Variables */

    /**
     * The time stamp when this scholarship was created.
     */
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups("scholarship")]
    private $created;

    /**
     * The user who created this scholarship.
     */
    #[ORM\Column(type: 'string')]
    #[Gedmo\Blameable(on: 'create')]
    #[Groups("scholarship")]
    private $createdBy;

    /**
     * The time stamp when this scholarship was last updated.
     */
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    #[Groups("scholarship")]
    private $updated;

    /**
     * The user who last updated this scholarship.
     */
    #[ORM\Column(type: 'string')]
    #[Gedmo\Blameable(on: 'update')]
    #[Groups("scholarship")]
    private $updatedBy;

    /**
     * The constructor of a scholarship.
     */
    public function __construct()
    {
        $this->programLinks = new ArrayCollection();
    }

    /* ************************** Getters and Setters ************************* */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getGpa(): ?string
    {
        return $this->gpa;
    }

    public function setGpa(?string $gpa): self
    {
        $this->gpa = $gpa;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
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

    public function getApplyDate(): ?\DateTimeInterface
    {
        return $this->applyDate;
    }

    public function setApplyDate(?\DateTimeInterface $applyDate): self
    {
        $this->applyDate = $applyDate;
        return $this;
    }

    public function getExpDate(): ?\DateTimeInterface
    {
        return $this->expDate;
    }

    public function setExpDate(?\DateTimeInterface $expDate): self
    {
        $this->expDate = $expDate;
        return $this;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function setCounty(?string $county): self
    {
        $this->county = $county;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getHighSchool(): ?string
    {
        return $this->highSchool;
    }

    public function setHighSchool(?string $highSchool): self
    {
        $this->highSchool = $highSchool;
        return $this;
    }

    public function getStandingClass(): ?string
    {
        return $this->standingClass;
    }

    public function setStandingClass(?string $standingClass): self
    {
        $this->standingClass = $standingClass;
        return $this;
    }

    public function getEnrollment(): ?string
    {
        return $this->enrollment;
    }

    public function setEnrollment(?string $enrollment): self
    {
        $this->enrollment = $enrollment;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getEthnicity(): ?string
    {
        return $this->ethnicity;
    }

    public function setEthnicity(?string $ethnicity): self
    {
        $this->ethnicity = $ethnicity;
        return $this;
    }

    public function getFafsa(): ?string
    {
        return $this->fafsa;
    }

    public function setFafsa(?string $fafsa): self
    {
        $this->fafsa = $fafsa;
        return $this;
    }

    public function getIsParent(): ?string
    {
        return $this->isParent;
    }

    public function setIsParent(?string $isParent): self
    {
        $this->isParent = $isParent;
        return $this;
    }

    public function getIsBilingual(): ?string
    {
        return $this->isBilingual;
    }

    public function setIsBilingual(?string $isBilingual): self
    {
        $this->isBilingual = $isBilingual;
        return $this;
    }

    public function getOrganizations(): ?string
    {
        return $this->organizations;
    }

    public function setOrganizations(?string $organizations): self
    {
        $this->organizations = $organizations;
        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getHousing(): ?string
    {
        return $this->housing;
    }

    public function setHousing(?string $housing): self
    {
        $this->housing = $housing;
        return $this;
    }

    public function getAppProc(): ?string
    {
        return $this->appProc;
    }

    public function setAppProc(?string $appProc): self
    {
        $this->appProc = $appProc;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function setContactId(?int $contactId): self
    {
        $this->contactId = $contactId;
        return $this;
    }

    /**
     * @return Collection<int, ScholarshipProgram>
     */
    public function getProgramLinks(): Collection
    {
        return $this->programLinks;
    }

    public function addProgramLink(ScholarshipProgram $programLink): self
    {
        if (!$this->programLinks->contains($programLink)) {
            $this->programLinks->add($programLink);
            $programLink->setScholarship($this);
        }
        return $this;
    }

    public function removeProgramLink(ScholarshipProgram $programLink): self
    {
        if ($this->programLinks->removeElement($programLink)) {
            if ($programLink->getScholarship() === $this) {
                $programLink->setScholarship(null);
            }
        }
        return $this;
    }

    /* ***************************** Gedmo Getters **************************** */

    public function getCreated()
    {
        return $this->created;
    }

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
