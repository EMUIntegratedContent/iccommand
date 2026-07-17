<?php

namespace App\Entity;

use App\Repository\SocialMediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SocialMediaRepository::class)]
#[ORM\Table(name: 'social_media')]
class SocialMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("social")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "A name is required.")]
    #[Groups("social")]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "The Facebook URL is not a valid URL.")]
    #[Groups("social")]
    private ?string $facebook_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "The X URL is not a valid URL.")]
    #[Groups("social")]
    private ?string $x_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "The YouTube URL is not a valid URL.")]
    #[Groups("social")]
    private ?string $youtube_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "The Instagram URL is not a valid URL.")]
    #[Groups("social")]
    private ?string $instagram_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "The LinkedIn URL is not a valid URL.")]
    #[Groups("social")]
    private ?string $linkedin_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "The TikTok URL is not a valid URL.")]
    #[Groups("social")]
    private ?string $tiktok_url = null;

    /* Gedmo Variables */

    /**
     * The time stamp when this entity was created.
     */
    #[ORM\Column(type: "datetime")]
    #[Gedmo\Timestampable(on: "create")]
    #[Groups("social")]
    private $created;

    /**
     * The user who created this entity.
     */
    #[ORM\Column(type: "string")]
    #[Gedmo\Blameable(on: "create")]
    #[Groups("social")]
    private $createdBy;

    /**
     * The time stamp when this entity was last updated.
     */
    #[ORM\Column(type: "datetime")]
    #[Gedmo\Timestampable(on: "update")]
    #[Groups("social")]
    private $updated;

    /**
     * The user who last updated this entity.
     */
    #[ORM\Column(type: "string")]
    #[Gedmo\Blameable(on: "update")]
    #[Groups("social")]
    private $updatedBy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebook_url;
    }

    public function setFacebookUrl(?string $facebook_url): static
    {
        $this->facebook_url = $facebook_url;

        return $this;
    }

    public function getXUrl(): ?string
    {
        return $this->x_url;
    }

    public function setXUrl(?string $x_url): static
    {
        $this->x_url = $x_url;

        return $this;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtube_url;
    }

    public function setYoutubeUrl(?string $youtube_url): static
    {
        $this->youtube_url = $youtube_url;

        return $this;
    }

    public function getInstagramUrl(): ?string
    {
        return $this->instagram_url;
    }

    public function setInstagramUrl(?string $instagram_url): static
    {
        $this->instagram_url = $instagram_url;

        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedin_url;
    }

    public function setLinkedinUrl(?string $linkedin_url): static
    {
        $this->linkedin_url = $linkedin_url;

        return $this;
    }

    public function getTiktokUrl(): ?string
    {
        return $this->tiktok_url;
    }

    public function setTiktokUrl(?string $tiktok_url): static
    {
        $this->tiktok_url = $tiktok_url;

        return $this;
    }

    /* ***************************** Gedmo Getters **************************** */

    /**
     * Obtains the time stamp when this entity was created.
     */
    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    /**
     * Obtains the user who created this entity.
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Obtains the time stamp when this entity was last updated.
     */
    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    /**
     * Obtains the user who last updated this entity.
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }
}
