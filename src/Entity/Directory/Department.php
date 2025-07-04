<?php

namespace App\Entity\Directory;

use App\Entity\Map\MapBuilding;
use App\Repository\Directory\DepartmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[ORM\Table(name: 'directory_department')]
class Department
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  #[Groups("department")]
  private ?int $id = null;

  #[ORM\Column(length: 150)]
  #[Groups("department")]
  private ?string $department = null;

  #[ORM\Column(type: 'text', nullable: true)]
  #[Groups("department")]
  private ?string $searchTerms = null;

  #[ORM\Column(length: 255, nullable: true)]
  #[Groups("department")]
  private ?string $mapBuildingName = null;

  /**
   * Many departments can belong to one building.
   */
  #[ORM\ManyToOne(targetEntity: MapBuilding::class)]
  #[ORM\JoinColumn(name: "map_building_id", referencedColumnName: "id", nullable: true, onDelete: "CASCADE")]
  #[Groups("department")]
  private ?MapBuilding $mapBuilding = null;

  #[ORM\Column(length: 255, nullable: true)]
  #[Groups("department")]
  private ?string $address1 = null;

  #[ORM\Column(length: 255, nullable: true)]
  #[Groups("department")]
  private ?string $address2 = null;

  #[ORM\Column(length: 100, nullable: true)]
  #[Groups("department")]
  private ?string $city = null;

  #[ORM\Column(length: 2, nullable: true)]
  #[Groups("department")]
  private ?string $state = null;

  #[ORM\Column(length: 10, nullable: true)]
  #[Groups("department")]
  private ?string $zip = null;

  #[ORM\Column(type: 'boolean', options: ['default' => 1])]
  #[Groups("department")]
  private bool $onCampus = true;

  #[ORM\Column(length: 20, nullable: true)]
  #[Groups("department")]
  private ?string $phone = null;

  #[ORM\Column(length: 20, nullable: true)]
  #[Groups("department")]
  private ?string $phoneAlt = null;

  #[ORM\Column(length: 20, nullable: true)]
  #[Groups("department")]
  private ?string $fax = null;

  #[ORM\Column(length: 100, nullable: true)]
  #[Groups("department")]
  private ?string $email = null;

  #[ORM\Column(length: 255, nullable: true)]
  #[Groups("department")]
  private ?string $website = null;

  #[ORM\Column(length: 255, nullable: true)]
  #[Groups("department")]
  private ?string $facultyList = null;

  #[ORM\Column(length: 255, nullable: true)]
  #[Groups("department")]
  private ?string $staffList = null;

  #[ORM\Column(type: 'date', nullable: true)]
  #[Groups("department")]
  private ?\DateTimeInterface $updated = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getDepartment(): ?string
  {
    return $this->department;
  }

  public function setDepartment(string $department): static
  {
    $this->department = $department;

    return $this;
  }

  public function getSearchTerms(): ?string
  {
    return $this->searchTerms;
  }

  public function setSearchTerms(?string $searchTerms): static
  {
    $this->searchTerms = $searchTerms;

    return $this;
  }

  public function getMapBuildingName(): ?string
  {
    return $this->mapBuildingName;
  }

  #[Groups("department")] // Means this is available in the API response
  public function getBuildingName(): ?string
  {
    return $this->mapBuilding ? $this->mapBuilding->getName() : null;
  }

  public function setMapBuildingName(?string $mapBuildingName): static
  {
    $this->mapBuildingName = $mapBuildingName;

    return $this;
  }

  public function getMapBuilding(): ?int
  {
    return $this->mapBuilding ? $this->mapBuilding->getId() : null;
  }

  public function setMapBuilding(?MapBuilding $mapBuilding): static
  {
    $this->mapBuilding = $mapBuilding;

    return $this;
  }

  public function getAddress1(): ?string
  {
    return $this->address1;
  }

  public function setAddress1(?string $address1): static
  {
    $this->address1 = $address1;

    return $this;
  }

  public function getAddress2(): ?string
  {
    return $this->address2;
  }

  public function setAddress2(?string $address2): static
  {
    $this->address2 = $address2;

    return $this;
  }

  public function getCity(): ?string
  {
    return $this->city;
  }

  public function setCity(?string $city): static
  {
    $this->city = $city;

    return $this;
  }

  public function getState(): ?string
  {
    return $this->state;
  }

  public function setState(?string $state): static
  {
    $this->state = $state;

    return $this;
  }

  public function getZip(): ?string
  {
    return $this->zip;
  }

  public function setZip(?string $zip): static
  {
    $this->zip = $zip;

    return $this;
  }

  public function isOnCampus(): bool
  {
    return $this->onCampus;
  }

  public function setOnCampus(bool $onCampus): static
  {
    $this->onCampus = $onCampus;

    return $this;
  }

  public function getPhone(): ?string
  {
    return $this->phone;
  }

  public function setPhone(?string $phone): static
  {
    $this->phone = $phone;

    return $this;
  }

  public function getPhoneAlt(): ?string
  {
    return $this->phoneAlt;
  }

  public function setPhoneAlt(?string $phoneAlt): static
  {
    $this->phoneAlt = $phoneAlt;

    return $this;
  }

  public function getFax(): ?string
  {
    return $this->fax;
  }

  public function setFax(?string $fax): static
  {
    $this->fax = $fax;

    return $this;
  }

  public function getEmail(): ?string
  {
    return $this->email;
  }

  public function setEmail(?string $email): static
  {
    $this->email = $email;

    return $this;
  }

  public function getWebsite(): ?string
  {
    return $this->website;
  }

  public function setWebsite(?string $website): static
  {
    $this->website = $website;

    return $this;
  }

  public function getFacultyList(): ?string
  {
    return $this->facultyList;
  }

  public function setFacultyList(?string $facultyList): static
  {
    $this->facultyList = $facultyList;

    return $this;
  }

  public function getStaffList(): ?string
  {
    return $this->staffList;
  }

  public function setStaffList(?string $staffList): static
  {
    $this->staffList = $staffList;

    return $this;
  }

  public function getUpdated(): ?\DateTimeInterface
  {
    return $this->updated;
  }

  public function setUpdated(?\DateTimeInterface $updated): static
  {
    $this->updated = $updated;

    return $this;
  }
}
