<?php

namespace App\Entity;

use App\Repository\DirectoryDepartmentsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DirectoryDepartmentsRepository::class)
 */
class DirectoryDepartments
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $updated;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $department;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $search_terms;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $map_building_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $state;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $zip;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $on_campus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone_alt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fax;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $faculty_list;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $staff_list;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function getSearchTerms(): ?string
    {
        return $this->search_terms;
    }

    public function setSearchTerms(?string $search_terms): self
    {
        $this->search_terms = $search_terms;

        return $this;
    }

    public function getMapBuildingName(): ?string
    {
        return $this->map_building_name;
    }

    public function setMapBuildingName(?string $map_building_name): self
    {
        $this->map_building_name = $map_building_name;

        return $this;
    }

    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    public function setAddress1(?string $address1): self
    {
        $this->address1 = $address1;

        return $this;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function setAddress2(?string $address2): self
    {
        $this->address2 = $address2;

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

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getOnCampus(): ?int
    {
        return $this->on_campus;
    }

    public function setOnCampus(?int $on_campus): self
    {
        $this->on_campus = $on_campus;

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

    public function getPhoneAlt(): ?string
    {
        return $this->phone_alt;
    }

    public function setPhoneAlt(?string $phone_alt): self
    {
        $this->phone_alt = $phone_alt;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): self
    {
        $this->fax = $fax;

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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getFacultyList(): ?string
    {
        return $this->faculty_list;
    }

    public function setFacultyList(?string $faculty_list): self
    {
        $this->faculty_list = $faculty_list;

        return $this;
    }

    public function getStaffList(): ?string
    {
        return $this->staff_list;
    }

    public function setStaffList(?string $staff_list): self
    {
        $this->staff_list = $staff_list;

        return $this;
    }
}
