<?php

namespace App\Entity;

use App\Repository\OuCampusSignupsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OuCampusSignupsRepository::class)
 */
class OuCampusSignups
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $full_name;

    /**
     * @ORM\Column(type="string", length=355)
     */
    private $emich_email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $site;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    private $new_user;

    /**
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    private $is_student;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $supervisors;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comments;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $submitted;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    public function setFullName(string $full_name): self
    {
        $this->full_name = $full_name;

        return $this;
    }

    public function getEmichEmail(): ?string
    {
        return $this->emich_email;
    }

    public function setEmichEmail(string $emich_email): self
    {
        $this->emich_email = $emich_email;

        return $this;
    }

    public function getSite(): ?string
    {
        return $this->site;
    }

    public function setSite(string $site): self
    {
        $this->site = $site;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getNewUser(): ?string
    {
        return $this->new_user;
    }

    public function setNewUser(?string $new_user): self
    {
        $this->new_user = $new_user;

        return $this;
    }

    public function getIsStudent(): ?string
    {
        return $this->is_student;
    }

    public function setIsStudent(?string $is_student): self
    {
        $this->is_student = $is_student;

        return $this;
    }

    public function getSupervisors(): ?string
    {
        return $this->supervisors;
    }

    public function setSupervisors(?string $supervisors): self
    {
        $this->supervisors = $supervisors;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getSubmitted(): ?\DateTimeInterface
    {
        return $this->submitted;
    }

    public function setSubmitted(?\DateTimeInterface $submitted): self
    {
        $this->submitted = $submitted;

        return $this;
    }
}
