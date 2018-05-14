<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\MultimediaRequest\MultimediaRequest;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GraphicRequestRepository")
 */
class GraphicRequest extends MultimediaRequest
{

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime(message="You must provide a valid completion date and time.")
     */
    private $completionDate;


    public function getCompletionDate(): ?\DateTimeInterface
    {
        return $this->completionDate;
    }

    public function setCompletionDate(\DateTimeInterface $completionDate): self
    {
        $this->completionDate = $completionDate;

        return $this;
    }
}
