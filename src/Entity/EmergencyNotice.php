<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: "emergency_notice")]
class EmergencyNotice
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private ?int $id = null;

    #[ORM\Column(name: "notice", type: "text")]
    private string $notice;

    #[Gedmo\Timestampable(on: "create")]
    #[ORM\Column(name: "created", type: "datetime")]
    private \DateTime $created;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "created_by", referencedColumnName: "id")]
    private User $createdBy;

    #[Gedmo\Timestampable(on: "update")]
    #[ORM\Column(name: "updated", type: "datetime")]
    private \DateTime $updated;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "updated_by", referencedColumnName: "id")]
    private User $updatedBy;

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

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    public function getUpdatedBy(): User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }
}
