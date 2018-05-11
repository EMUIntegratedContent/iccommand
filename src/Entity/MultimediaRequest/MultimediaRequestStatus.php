<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\MultimediaRequestStatusRepository")
 * @Serializer\XmlRoot("multimediaStatus")
 */
class MultimediaRequestStatus
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
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\SerializedName("statusSlug")
     * @Assert\NotBlank(message="You must provide a slug.")
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
