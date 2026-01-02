<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: "emergency_banner")]
class EmergencyBanner
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private ?int $id = null;

    #[ORM\Column(name: "display_banner", type: "boolean", options: ["default" => 0])]
    private bool $displayBanner = false;

    #[ORM\Column(name: "severity", type: "string", length: 10, nullable: true, enumType: EmergencySeverity::class)]
    private ?EmergencySeverity $severity = null;

    #[ORM\Column(name: "force_emergency_page", type: "boolean", options: ["default" => 0])]
    private bool $forceEmergencyPage = false;

    #[ORM\Column(name: "banner_message", type: "text", nullable: true)]
    private ?string $bannerMessage = null;

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

    public function getDisplayBanner(): bool
    {
        return $this->displayBanner;
    }

    public function setDisplayBanner(bool $displayBanner): self
    {
        $this->displayBanner = $displayBanner;
        return $this;
    }

    public function getSeverity(): ?EmergencySeverity
    {
        return $this->severity;
    }

    public function setSeverity(?EmergencySeverity $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    public function getForceEmergencyPage(): bool
    {
        return $this->forceEmergencyPage;
    }

    public function setForceEmergencyPage(bool $forceEmergencyPage): self
    {
        $this->forceEmergencyPage = $forceEmergencyPage;
        return $this;
    }

    public function getBannerMessage(): ?string
    {
        return $this->bannerMessage;
    }

    public function setBannerMessage(?string $bannerMessage): self
    {
        $this->bannerMessage = $bannerMessage;
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
