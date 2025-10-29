<?php

namespace App\Entity\Emergency;

use App\Entity\Emergency\EmergencySeverity;
use App\Repository\Emergency\EmergencyRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EmergencyRepository::class)]
#[ORM\Table(name: "emergency_banner")]
class EmergencyBanner
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private ?int $id = null;

    #[ORM\Column(name: "display_banner", type: "boolean", options: ["default" => 0])]
    #[Groups("banner")]
    private bool $displayBanner = false;

    #[ORM\Column(name: "severity", type: "string", length: 10, nullable: true, enumType: EmergencySeverity::class)]
    #[Groups("banner")]
    private ?EmergencySeverity $severity = null;

    #[ORM\Column(name: "force_emergency_page", type: "boolean", options: ["default" => 0])]
    #[Groups("banner")]
    private bool $forceEmergencyPage = false;

    #[ORM\Column(name: "banner_title", type: "text", nullable: true)]
    #[Groups("banner")]
    private ?string $bannerTitle = null;

    #[ORM\Column(name: "banner_message", type: "text", nullable: true)]
    #[Groups("banner")]
    private ?string $bannerMessage = null;

    #[Gedmo\Timestampable(on: "update")]
    #[ORM\Column(name: "updated", type: "datetime")]
		#[Groups("banner")]
    private \DateTime $updated;

    #[ORM\Column(name: "updated_by", type: "integer")]
    private int $updatedBy;

    // Return the username, not the user ID.
    #[Groups("banner")]
    private ?string $updatedByUsername = null;

    // Emergency notices (not persisted, populated by repository)
    #[Groups("banner")]
    private array $notices = [];

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

    public function getBannerTitle(): ?string
    {
        return $this->bannerTitle;
    }

    public function setBannerMessage(?string $bannerMessage): self
    {
        $this->bannerMessage = $bannerMessage;
        return $this;
    }

    public function setBannerTitle(?string $bannerTitle): self
    {
        $this->bannerTitle = $bannerTitle;
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

    public function getNotices(): array
    {
        return $this->notices;
    }

    public function setNotices(array $notices): self
    {
        $this->notices = $notices;
        return $this;
    }
}
