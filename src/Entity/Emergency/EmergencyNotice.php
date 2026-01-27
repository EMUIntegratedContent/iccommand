<?php

namespace App\Entity\Emergency;

use App\Repository\Emergency\EmergencyNoticeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;  
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EmergencyNoticeRepository::class)]
#[ORM\Table(name: "emergency_notice")]
class EmergencyNotice
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private ?int $id = null;

    #[ORM\Column(name: "notice", type: "text")]
    #[Groups(['banner'])]
    private string $notice;

    #[Gedmo\Timestampable(on: "create")]
    #[ORM\Column(name: "created", type: "datetime")]
    private \DateTime $created;

    #[ORM\Column(name: "created_by", type: "integer")]
    private int $createdBy;

    #[Gedmo\Timestampable(on: "update")]
    #[ORM\Column(name: "updated", type: "datetime")]
    private \DateTime $updated;

    #[ORM\Column(name: "updated_by", type: "integer")]
    private int $updatedBy;

    private ?string $createdByUsername = null;
    private ?string $updatedByUsername = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotice(): string
    {
        return $this->notice;
    }

    public function setNotice(string $notice): self
    {
        $this->notice = $notice;
        return $this;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedByUsername(): ?string
    {
        return $this->createdByUsername;
    }

    public function setCreatedByUsername(?string $username): self
    {
        $this->createdByUsername = $username;
        return $this;
    }

    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    public function getUpdatedBy(): int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getUpdatedByUsername(): ?string
    {
        return $this->updatedByUsername;
    }

    public function setUpdatedByUsername(?string $username): self
    {
        $this->updatedByUsername = $username;
        return $this;
    }
}
