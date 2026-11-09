<?php
namespace App\Entity\Ic;

use App\Repository\Ic\IcContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A person or organization to contact, shared across IC Command apps. Programs, scholarships
 * and buildings can each have any number of them, through the program_contact,
 * scholarship_contact and map_building_contact link tables.
 */
#[ORM\Entity(repositoryClass: IcContactRepository::class)]
#[ORM\Table(name: 'ic_contacts')]
class IcContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("ic")]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: IcContactType::class)]
    #[Assert\NotNull(message: 'Choose a contact type.')]
    #[Groups("ic")]
    private ?IcContactType $type = null;

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
     * The time stamp when this contact was created.
     */
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups("ic")]
    private $created;

    /**
     * The user who created this contact.
     */
    #[ORM\Column(type: 'string')]
    #[Gedmo\Blameable(on: 'create')]
    #[Groups("ic")]
    private $createdBy;

    /**
     * The time stamp when this contact was last updated.
     */
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    #[Groups("ic")]
    private $updated;

    /**
     * The user who last updated this contact.
     */
    #[ORM\Column(type: 'string')]
    #[Gedmo\Blameable(on: 'update')]
    #[Groups("ic")]
    private $updatedBy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?IcContactType
    {
        return $this->type;
    }

    public function setType(?IcContactType $type): self
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
     * The name for pickers and lists: "Last, First" or the organization. IcContactRepository::
     * DISPLAY_NAME_SQL is the same rule in SQL.
     */
    #[Groups("ic")]
    public function getDisplayName(): string
    {
        if ($this->type === IcContactType::PERSON) {
            return $this->lastName . ', ' . $this->firstName;
        }

        return (string) $this->organization;
    }

    /**
     * What the public feeds publish about a contact. Everything else (room, area, Forager and
     * image URLs, audit fields, id) stays private, so a new field is private until added here.
     *
     * @return array{name: string, email: ?string, phone: ?string, url: ?string}
     */
    public function toPublicArray(): array
    {
        return [
            'name' => $this->type === IcContactType::PERSON
                ? $this->firstName . ' ' . $this->lastName
                : (string) $this->organization,
            'email' => $this->email,
            'phone' => $this->phone,
            'url' => $this->url,
        ];
    }

    /**
     * A person needs both names and an organization needs its name, or the contact has no
     * name to show.
     */
    #[Assert\Callback]
    public function validateName(ExecutionContextInterface $context): void
    {
        $required = match ($this->type) {
            IcContactType::PERSON => [
                'lastName' => [$this->lastName, 'A last name is required.'],
                'firstName' => [$this->firstName, 'A first name is required.'],
            ],
            IcContactType::ORGANIZATION => [
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
