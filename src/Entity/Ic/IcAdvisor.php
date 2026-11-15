<?php
namespace App\Entity\Ic;

use App\Entity\Map\MapBuilding;
use App\Repository\Ic\IcAdvisorRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * An advisor (a person or organization), shared across IC Command apps. Programs and
 * scholarships can each have any number of them, through the program_advisor and
 * scholarship_advisor link tables. An advisor can be in one building.
 */
#[ORM\Entity(repositoryClass: IcAdvisorRepository::class)]
#[ORM\Table(name: 'ic_advisors')]
class IcAdvisor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("ic")]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: IcAdvisorType::class)]
    #[Assert\NotNull(message: 'Choose an advisor type.')]
    #[Groups("ic")]
    private ?IcAdvisorType $type = null;

    #[ORM\Column(name: 'last_name', length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Last name must be 100 characters or less.')]
    #[Groups("ic")]
    private ?string $lastName = null;

    #[ORM\Column(name: 'first_name', length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'First name must be 100 characters or less.')]
    #[Groups("ic")]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Organization must be 255 characters or less.')]
    #[Groups("ic")]
    private ?string $organization = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 255, maxMessage: 'Email must be 255 characters or less.')]
    #[Groups("ic")]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50, maxMessage: 'Phone must be 50 characters or less.')]
    #[Groups("ic")]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Room must be 255 characters or less.')]
    #[Groups("ic")]
    private ?string $room = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Area must be 255 characters or less.')]
    #[Groups("ic")]
    private ?string $area = null;

    /**
     * The building the advisor is in, if any. Deleting the building clears it. Serialized
     * through buildingId/buildingName rather than as a nested building.
     */
    #[ORM\ManyToOne(targetEntity: MapBuilding::class)]
    #[ORM\JoinColumn(name: 'building_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?MapBuilding $building = null;

    #[ORM\Column(length: 1024, nullable: true)]
    #[Assert\Url(message: 'Please enter a valid URL.', requireTld: true)]
    #[Assert\Length(max: 1024, maxMessage: 'URL must be 1024 characters or less.')]
    #[Groups("ic")]
    private ?string $url = null;

    #[ORM\Column(name: 'forager_url', length: 1024, nullable: true)]
    #[Assert\Url(message: 'Please enter a valid Forager URL.', requireTld: true)]
    #[Assert\Length(max: 1024, maxMessage: 'Forager URL must be 1024 characters or less.')]
    #[Groups("ic")]
    private ?string $foragerUrl = null;

    #[ORM\Column(name: 'image_url', length: 1024, nullable: true)]
    #[Assert\Url(message: 'Please enter a valid image URL.', requireTld: true)]
    #[Assert\Length(max: 1024, maxMessage: 'Image URL must be 1024 characters or less.')]
    #[Groups("ic")]
    private ?string $imageUrl = null;

    /* Gedmo Variables */

    /**
     * The time stamp when this advisor was created.
     */
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups("ic")]
    private $created;

    /**
     * The user who created this advisor.
     */
    #[ORM\Column(type: 'string')]
    #[Gedmo\Blameable(on: 'create')]
    #[Groups("ic")]
    private $createdBy;

    /**
     * The time stamp when this advisor was last updated.
     */
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    #[Groups("ic")]
    private $updated;

    /**
     * The user who last updated this advisor.
     */
    #[ORM\Column(type: 'string')]
    #[Gedmo\Blameable(on: 'update')]
    #[Groups("ic")]
    private $updatedBy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?IcAdvisorType
    {
        return $this->type;
    }

    public function setType(?IcAdvisorType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
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

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function setRoom(?string $room): self
    {
        $this->room = $room;
        return $this;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(?string $area): self
    {
        $this->area = $area;
        return $this;
    }

    #[Ignore]
    public function getBuilding(): ?MapBuilding
    {
        return $this->building;
    }

    public function setBuilding(?MapBuilding $building): self
    {
        $this->building = $building;
        return $this;
    }

    #[Groups("ic")]
    public function getBuildingId(): ?int
    {
        return $this->building?->getId();
    }

    #[Groups("ic")]
    public function getBuildingName(): ?string
    {
        return $this->building?->getName();
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

    public function getForagerUrl(): ?string
    {
        return $this->foragerUrl;
    }

    public function setForagerUrl(?string $foragerUrl): self
    {
        $this->foragerUrl = $foragerUrl;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

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

    /**
     * Marks it as changed now, for a change Gedmo can't see (only its links changed). Gedmo
     * still sets updatedBy on the flush, since updated is then a changed column.
     */
    public function touch(): self
    {
        $this->updated = new \DateTime();
        return $this;
    }

    /**
     * The name for pickers and lists: "Last, First" or the organization. IcAdvisorRepository::
     * DISPLAY_NAME_SQL is the same rule in SQL.
     */
    #[Groups("ic")]
    public function getDisplayName(): string
    {
        if ($this->type === IcAdvisorType::PERSON) {
            return $this->lastName . ', ' . $this->firstName;
        }

        return (string) $this->organization;
    }

    /**
     * What the public feeds publish about an advisor. Everything else (room, area, Forager and
     * image URLs, audit fields, its id and its building's id) stays private, so a new field is
     * private until added here.
     *
     * @return array{name: string, email: ?string, phone: ?string, url: ?string, buildingName: ?string}
     */
    public function toPublicArray(): array
    {
        return [
            'name' => $this->type === IcAdvisorType::PERSON
                ? $this->firstName . ' ' . $this->lastName
                : (string) $this->organization,
            'email' => $this->email,
            'phone' => $this->phone,
            'url' => $this->url,
						'room' => $this->room,
						'area' => $this->area,
            'buildingName' => $this->building?->getName(),
        ];
    }

    /**
     * A person needs both names and an organization needs its name, or the advisor has no
     * name to show.
     */
    #[Assert\Callback]
    public function validateName(ExecutionContextInterface $context): void
    {
        $required = match ($this->type) {
            IcAdvisorType::PERSON => [
                'lastName' => [$this->lastName, 'A last name is required.'],
                'firstName' => [$this->firstName, 'A first name is required.'],
            ],
            IcAdvisorType::ORGANIZATION => [
                'organization' => [$this->organization, 'An organization name is required.'],
            ],
            null => [],
        };

        foreach ($required as $path => [$value, $message]) {
            if ($value === null || trim($value) === '') {
                $context->buildViolation($message)->atPath($path)->addViolation();
            }
        }
    }
}
