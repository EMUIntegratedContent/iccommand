<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\MultimediaRequestAssigneeStatusRepository")
 */
class MultimediaRequestAssigneeStatus
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="You must provide a status.")
     * @Groups("multi")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     * @SerializedName("statusSlug")
     * @Assert\NotBlank(message="You must provide a slug.")
     * @Groups("multi")
     */
    private $statusSlug;

    public function getId()
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusSlug(): ?string
    {
        return $this->statusSlug;
    }

    public function setStatusSlug(string $statusSlug): self
    {
        $this->statusSlug = $statusSlug;

        return $this;
    }
}
