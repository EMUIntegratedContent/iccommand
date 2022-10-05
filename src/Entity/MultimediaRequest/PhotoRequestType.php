<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\PhotoRequestTypeRepository")
 */
class PhotoRequestType
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("multi")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="You must provide a request type.")
     * @SerializedName("requestType")
     * @Groups("multi")
     */
    private $requestType;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="You must provide a slug.")
     * @Groups("multi")
     */
    private $slug;

    public function getId()
    {
        return $this->id;
    }

    public function getRequestType(): ?string
    {
        return $this->requestType;
    }

    public function setRequestType(string $requestType): self
    {
        $this->requestType = $requestType;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
