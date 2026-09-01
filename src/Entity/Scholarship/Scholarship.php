<?php
namespace App\Entity\Scholarship;

use App\Repository\Scholarship\ScholarshipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A scholarship offered at EMU. Column names keep the source system's `schlrshp_*`
 * prefix to ease future data import; PHP properties are clean camelCase.
 */
#[ORM\Entity(repositoryClass: ScholarshipRepository::class)]
#[ORM\Table(name: 'scholarships_scholarship')]
class Scholarship
{
    /* ***************************** Option Lists ***************************** */

    /**
     * Option lists for the constrained fields, taken from the legacy FinAid admin.
     * The validation constraints use these, and /api/scholarships/options serves them to the form.
     * The legacy "N/A" choice means "no restriction" and is stored as null, so it is not listed.
     */
    public const GENDER_OPTIONS = ['Male', 'Female'];

    public const ETHNICITY_OPTIONS = [
        'Black/African American',
        'Hispanic/Latino',
        'Asian/Pacific Islander',
        'Native American',
        'Chaldean/Arabic',
        'Other',
    ];

    /**
     * Two decimal places to match the DECIMAL(3,2) column, which reads back as "3.50".
     */
    public const GPA_OPTIONS = [
        '2.50', '2.60', '2.70', '2.80', '2.90',
        '3.00', '3.10', '3.20', '3.30', '3.40',
        '3.50', '3.60', '3.70', '3.80', '3.90',
        '4.00',
    ];

    /**
     * Class standing is multi-select and is stored comma separated.
     */
    public const CLASS_STANDING_OPTIONS = [
        'Entering Freshman',
        'Freshman',
        'Sophomore',
        'Junior',
        'Senior',
        'Graduate',
    ];

    public const HOUSING_OPTIONS = ['Yes', 'No'];

    public const TRANSFER_OPTIONS = ['Yes', 'No', 'Both'];

    /**
     * The legacy list duplicates PA and omits ME and NV. Corrected here, keeping DC and VI.
     */
    public const STATE_OPTIONS = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL',
        'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME',
        'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH',
        'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI',
        'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'VI', 'WA', 'WV',
        'WI', 'WY',
    ];

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
     * A short overview of this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_overview', type: 'text', nullable: true)]
    #[Groups("scholarship")]
    private ?string $overview = null;

    /**
     * Whether this scholarship is a catch-all.
     */
    #[ORM\Column(name: 'schlrshp_catch_all', type: 'boolean', options: ['default' => 0])]
    #[Groups("scholarship")]
    private bool $catchAll = false;

    /**
     * The minimum GPA required for this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_gpa', type: 'decimal', precision: 3, scale: 2, nullable: true)]
    #[Assert\Choice(choices: self::GPA_OPTIONS, message: "Choose a GPA from the list.")]
    #[Groups("scholarship")]
    private ?string $gpa = null;

    /**
     * The URL with more information about this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_url', type: 'string', length: 255, nullable: true)]
    #[Assert\Url(message: "Provide a valid URL.")]
    #[Assert\Length(max: 255)]
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
    #[Assert\GreaterThanOrEqual(propertyPath: 'applyDate', message: "The expiration date cannot come before the apply by date.")]
    #[Groups("scholarship")]
    private ?\DateTimeInterface $expDate = null;

    /**
     * The county eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_county', type: 'string', length: 160, nullable: true)]
    #[Assert\Length(max: 160)]
    #[Groups("scholarship")]
    private ?string $county = null;

    /**
     * The city eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_city', type: 'string', length: 160, nullable: true)]
    #[Assert\Length(max: 160)]
    #[Groups("scholarship")]
    private ?string $city = null;

    /**
     * The state eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_state', type: 'string', length: 160, nullable: true)]
    #[Assert\Choice(choices: self::STATE_OPTIONS, message: "Choose a state from the list.")]
    #[Groups("scholarship")]
    private ?string $state = null;

    /**
     * The high school eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_high_school', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups("scholarship")]
    private ?string $highSchool = null;

    /**
     * The standing / class eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_standing_class', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups("scholarship")]
    private ?string $standingClass = null;

    /**
     * The enrollment eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_enrollment', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups("scholarship")]
    private ?string $enrollment = null;

    /**
     * The gender eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_gender', type: 'string', length: 10, nullable: true)]
    #[Assert\Choice(choices: self::GENDER_OPTIONS, message: "Choose a gender from the list.")]
    #[Groups("scholarship")]
    private ?string $gender = null;

    /**
     * The ethnicity eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_ethnicity', type: 'string', length: 255, nullable: true)]
    #[Assert\Choice(choices: self::ETHNICITY_OPTIONS, message: "Choose an ethnicity from the list.")]
    #[Groups("scholarship")]
    private ?string $ethnicity = null;

    /**
     * The awarding college. Loose FK to program_colleges.id, mirroring how
     * App\Entity\Programs\Programs references colleges — a plain int with no Doctrine
     * association and no database FK constraint.
     */
    #[ORM\Column(name: 'schlrshp_college_id', type: 'integer', nullable: true)]
    #[Groups("scholarship")]
    private ?int $collegeId = null;

    /**
     * The awarding department. Loose FK to program_departments.id, same convention as
     * $collegeId above.
     */
    #[ORM\Column(name: 'schlrshp_department_id', type: 'integer', nullable: true)]
    #[Groups("scholarship")]
    private ?int $departmentId = null;

    /**
     * The FAFSA eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_is_fafsa', type: 'boolean', options: ['default' => 0])]
    #[Groups("scholarship")]
    private bool $isFafsa = false;

    /**
     * The parent-status eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_is_parent', type: 'boolean', options: ['default' => 0])]
    #[Groups("scholarship")]
    private bool $isParent = false;

    /**
     * The bilingual eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_is_bilingual', type: 'boolean', options: ['default' => 0])]
    #[Groups("scholarship")]
    private bool $isBilingual = false;

    /**
     * The organizations eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_organizations', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups("scholarship")]
    private ?string $organizations = null;

    /**
     * The keywords associated with this scholarship.
     */
    #[ORM\Column(name: 'schlrshp_keywords', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups("scholarship")]
    private ?string $keywords = null;

    /**
     * Whether this scholarship is available to transfer students: "Yes", "No" or "Both".
     */
    #[ORM\Column(name: 'schlrshp_transfer', type: 'string', length: 5, nullable: true)]
    #[Assert\Choice(choices: self::TRANSFER_OPTIONS, message: "Choose a transfer option from the list.")]
    #[Groups("scholarship")]
    private ?string $transfer = null;

    /**
     * The housing eligibility criterion.
     */
    #[ORM\Column(name: 'schlrshp_housing', type: 'string', length: 4, nullable: true)]
    #[Assert\Choice(choices: self::HOUSING_OPTIONS, message: "Choose a housing option from the list.")]
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
    #[Assert\Length(max: 255)]
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

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): self
    {
        $this->overview = $overview;
        return $this;
    }

    public function isCatchAll(): bool
    {
        return $this->catchAll;
    }

    public function setCatchAll(bool $catchAll): self
    {
        $this->catchAll = $catchAll;
        return $this;
    }

    public function getGpa(): ?string
    {
        return $this->gpa;
    }

    /**
     * Pad the GPA to two decimals so "3.5" and "3.50" are stored the same way.
     * @param string|null $gpa
     * @return $this
     */
    public function setGpa(?string $gpa): self
    {
        if ($gpa === null || $gpa === '') {
            $this->gpa = null;
            return $this;
        }

        $this->gpa = is_numeric($gpa) ? number_format((float)$gpa, 2, '.', '') : $gpa;
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

    public function getCollegeId(): ?int
    {
        return $this->collegeId;
    }

    public function setCollegeId(?int $collegeId): self
    {
        $this->collegeId = $collegeId;
        return $this;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function setDepartmentId(?int $departmentId): self
    {
        $this->departmentId = $departmentId;
        return $this;
    }

    public function getIsFafsa(): bool
    {
        return $this->isFafsa;
    }

    public function setIsFafsa(bool $isFafsa): self
    {
        $this->isFafsa = $isFafsa;
        return $this;
    }

    public function getIsParent(): bool
    {
        return $this->isParent;
    }

    public function setIsParent(bool $isParent): self
    {
        $this->isParent = $isParent;
        return $this;
    }

    public function getIsBilingual(): bool
    {
        return $this->isBilingual;
    }

    public function setIsBilingual(bool $isBilingual): self
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

    public function getTransfer(): ?string
    {
        return $this->transfer;
    }

    public function setTransfer(?string $transfer): self
    {
        $this->transfer = $transfer;
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

    /* ****************************** Validation ****************************** */

    /**
     * Check each class standing, since they are stored as one comma separated string.
     * @param ExecutionContextInterface $context
     * @return void
     */
    #[Assert\Callback]
    public function validateStandingClass(ExecutionContextInterface $context): void
    {
        if (!$this->standingClass) {
            return;
        }

        foreach (explode(',', $this->standingClass) as $standing) {
            $standing = trim($standing);

            if ($standing !== '' && !in_array($standing, self::CLASS_STANDING_OPTIONS, true)) {
                $context->buildViolation('"{{ standing }}" is not a valid class standing.')
                    ->setParameter('{{ standing }}', $standing)
                    ->atPath('standingClass')
                    ->addViolation();
            }
        }
    }
}
