<?php

namespace App\Entity\PhotoRequest;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\PhotoRequest\PhotoRequestRepository")]
class PhotoRequest
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column(type: 'integer')]
  private ?int $id = null;

  #[ORM\Column(type: 'string', length: 255, enumType: 'string')]
  #[Assert\Choice(choices: ['archive', 'photoshoot'])]
  #[Assert\NotBlank]
  private string $shootType = 'photoshoot';

  #[ORM\Column(type: 'string', length: 255)]
  #[Assert\NotBlank]
  private string $firstName;

  #[ORM\Column(type: 'string', length: 255)]
  #[Assert\NotBlank]
  private string $lastName;

  #[ORM\Column(type: 'string', length: 255)]
  #[Assert\NotBlank]
  #[Assert\Email]
  private string $email;

  #[ORM\Column(type: 'string', length: 12)]
  #[Assert\NotBlank]
  private string $phone;

  #[ORM\Column(type: 'string', length: 255)]
  #[Assert\NotBlank]
  private string $department;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $shootName = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $photoType = null;

  #[ORM\Column(type: 'date', nullable: true)]
  private ?\DateTimeInterface $shootDate = null;

  #[ORM\Column(type: 'time', nullable: true)]
  private ?\DateTimeInterface $startTime = null;

  #[ORM\Column(type: 'time', nullable: true)]
  private ?\DateTimeInterface $endTime = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $location = null;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $description = null;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $photoExplaination = null;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $intendedUse = null;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $forUse = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $url = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $designer = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $category = null;

  #[ORM\Column(type: 'integer')]
  private int $assigned = 0;

  #[ORM\ManyToOne(targetEntity: User::class)]
  #[ORM\JoinColumn(name: 'assigned_to', referencedColumnName: 'id', nullable: true)]
  private ?User $assignedTo = null;

  #[ORM\Column(type: 'integer', nullable: true)]
  private ?int $declined = 0;

  #[ORM\Column(type: 'integer')]
  private int $completed = 0;

  #[ORM\Column(type: 'datetime')]
  private \DateTimeInterface $submitted;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $longStatus = null;

  #[ORM\Column(type: 'string', length: 255, enumType: 'string', nullable: true)]
  #[Assert\Choice(choices: ['WC', 'IP', 'DG'])]
  private ?string $status = null;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $eventDesc = null;

  public function __construct()
  {
    $this->submitted = new \DateTime();
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getShootType(): string
  {
    return $this->shootType;
  }

  public function setShootType(string $shootType): self
  {
    $this->shootType = $shootType;
    return $this;
  }

  public function getFirstName(): string
  {
    return $this->firstName;
  }

  public function setFirstName(string $firstName): self
  {
    $this->firstName = $firstName;
    return $this;
  }

  public function getLastName(): string
  {
    return $this->lastName;
  }

  public function setLastName(string $lastName): self
  {
    $this->lastName = $lastName;
    return $this;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function setEmail(string $email): self
  {
    $this->email = $email;
    return $this;
  }

  public function getPhone(): string
  {
    return $this->phone;
  }

  public function setPhone(string $phone): self
  {
    $this->phone = $phone;
    return $this;
  }

  public function getDepartment(): string
  {
    return $this->department;
  }

  public function setDepartment(string $department): self
  {
    $this->department = $department;
    return $this;
  }

  public function getShootName(): ?string
  {
    return $this->shootName;
  }

  public function setShootName(?string $shootName): self
  {
    $this->shootName = $shootName;
    return $this;
  }

  public function getPhotoType(): ?string
  {
    return $this->photoType;
  }

  public function setPhotoType(?string $photoType): self
  {
    $this->photoType = $photoType;
    return $this;
  }

  public function getShootDate(): ?\DateTimeInterface
  {
    return $this->shootDate;
  }

  public function setShootDate(?\DateTimeInterface $shootDate): self
  {
    $this->shootDate = $shootDate;
    return $this;
  }

  public function getStartTime(): ?\DateTimeInterface
  {
    return $this->startTime;
  }

  public function setStartTime(?\DateTimeInterface $startTime): self
  {
    $this->startTime = $startTime;
    return $this;
  }

  public function getEndTime(): ?\DateTimeInterface
  {
    return $this->endTime;
  }

  public function setEndTime(?\DateTimeInterface $endTime): self
  {
    $this->endTime = $endTime;
    return $this;
  }

  public function getLocation(): ?string
  {
    return $this->location;
  }

  public function setLocation(?string $location): self
  {
    $this->location = $location;
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

  public function getPhotoExplaination(): ?string
  {
    return $this->photoExplaination;
  }

  public function setPhotoExplaination(?string $photoExplaination): self
  {
    $this->photoExplaination = $photoExplaination;
    return $this;
  }

  public function getIntendedUse(): ?string
  {
    return $this->intendedUse;
  }

  public function setIntendedUse(?string $intendedUse): self
  {
    $this->intendedUse = $intendedUse;
    return $this;
  }

  public function getForUse(): ?string
  {
    return $this->forUse;
  }

  public function setForUse(?string $forUse): self
  {
    $this->forUse = $forUse;
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

  public function getDesigner(): ?string
  {
    return $this->designer;
  }

  public function setDesigner(?string $designer): self
  {
    $this->designer = $designer;
    return $this;
  }

  public function getCategory(): ?string
  {
    return $this->category;
  }

  public function setCategory(?string $category): self
  {
    $this->category = $category;
    return $this;
  }

  public function getAssigned(): int
  {
    return $this->assigned;
  }

  public function setAssigned(int $assigned): self
  {
    $this->assigned = $assigned;
    return $this;
  }

  public function getAssignedTo(): ?User
  {
    return $this->assignedTo;
  }

  public function setAssignedTo(?User $assignedTo): self
  {
    $this->assignedTo = $assignedTo;
    return $this;
  }

  public function getDeclined(): ?int
  {
    return $this->declined;
  }

  public function setDeclined(?int $declined): self
  {
    $this->declined = $declined;
    return $this;
  }

  public function getCompleted(): int
  {
    return $this->completed;
  }

  public function setCompleted(int $completed): self
  {
    $this->completed = $completed;
    return $this;
  }

  public function getSubmitted(): \DateTimeInterface
  {
    return $this->submitted;
  }

  public function setSubmitted(\DateTimeInterface $submitted): self
  {
    $this->submitted = $submitted;
    return $this;
  }

  public function getLongStatus(): ?string
  {
    return $this->longStatus;
  }

  public function setLongStatus(?string $longStatus): self
  {
    $this->longStatus = $longStatus;
    return $this;
  }

  public function getStatus(): ?string
  {
    return $this->status;
  }

  public function setStatus(?string $status): self
  {
    $this->status = $status;
    return $this;
  }

  public function getEventDesc(): ?string
  {
    return $this->eventDesc;
  }

  public function setEventDesc(?string $eventDesc): self
  {
    $this->eventDesc = $eventDesc;
    return $this;
  }
}
