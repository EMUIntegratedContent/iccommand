<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "user")]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[Groups(['photos'])]
    private ?int $id = null;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\Column(type: "string", length: 50)]
    #[Groups(['photos'])]
    private ?string $username;

    #[ORM\Column(type: "string")]
    private string $password;

    #[ORM\Column(type: "boolean")]
    private bool $enabled;

    #[ORM\Column(name: "firstname", type: "string", length: 255, nullable: true)]
    #[Serializer\SerializedName("firstName")]
    #[Groups(['photos'])]
    private $firstName;

    #[ORM\Column(name: "lastname", type: "string", length: 255, nullable: true)]
    #[Serializer\SerializedName("lastName")]
    #[Groups(['photos'])]
    private $lastName;

    #[ORM\Column(name: "email", type: "string", length: 50, nullable: false)]
    private $email;

    #[ORM\Column(name: "jobtitle", type: "string", length: 255, nullable: true)]
    #[Serializer\SerializedName("jobTitle")]
    private $jobTitle;

    #[ORM\Column(name: "department", type: "string", length: 255, nullable: true)]
    private $department;

    #[ORM\Column(name: "phone", type: "string", length: 16, nullable: true)]
    private $phone;

    #[ORM\OneToOne(targetEntity: "App\Entity\UserImage")]
    #[ORM\JoinColumn(name: "image_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    protected $image;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getEnabled(): int
    {
        return $this->enabled;
    }

    public function setEnabled(int $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Get the formatted name in the format: Last Name, First Name (username)
     */
    #[Groups(['photos'])]
    public function getFormattedName(): string
    {
        $parts = [];

        if ($this->lastName) {
            $parts[] = $this->lastName;
        }

        if ($this->firstName) {
            $parts[] = $this->firstName;
        }

        $name = implode(', ', $parts);

        // if ($this->username) {
        //     $name .= ' (' . $this->username . ')';
        // }

        return $name ?: '---';
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setImage(UserImage $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getImage(): ?UserImage
    {
        return $this->image;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
