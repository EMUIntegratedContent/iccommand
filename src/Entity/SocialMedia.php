<?php

namespace App\Entity;

use App\Repository\SocialMediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocialMediaRepository::class)]
class SocialMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $facebook_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $x_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $youtube_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $instagram_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $linkedin_url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $tiktok_url = null;

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
}
