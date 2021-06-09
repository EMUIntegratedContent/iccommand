<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\MultimediaRequest\PublicationRequestType;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\PublicationRequestRepository")
 */
class PublicationRequest extends MultimediaRequest
{

    const REQUEST_TYPE = 'publication';

    /**
     * @ORM\Column(type="date")
     * @Assert\DateTime(message="You must provide a valid completion date.")
     * @Serializer\Type("DateTime<'Y-m-d'>")
     * @Serializer\SerializedName("completionDate")
     */
    private $completionDate;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\Length(
     *      max = 20,
     *      maxMessage = "Intended use cannot be longer than {{ limit }} characters"
     * )
     * @Serializer\SerializedName("intendedUse")
     */
    private $intendedUse;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull(message="Is photography required for this request?")
     * @Serializer\SerializedName("isPhotographyRequired")
     */
    private $isPhotographyRequired;

    /**
     * Many publication requests can have one type (e.g. "brochure" or "graphic")
     * @ORM\ManyToOne(targetEntity="PublicationRequestType")
     * @Serializer\SerializedName("publicationRequestType")
     */
    private $publicationRequestType;


    public function __construct() {
        parent::__construct();
    }

    public function getRequestType(){
        return constant("self::REQUEST_TYPE");
    }

    public function getCompletionDate(): ?\DateTimeInterface
    {
        return $this->completionDate;
    }

    public function setCompletionDate(\DateTimeInterface $completionDate): self
    {
        $this->completionDate = $completionDate;

        return $this;
    }

    public function getIntendedUse(): ?string
    {
        return $this->intendedUse;
    }

    public function setIntendedUse(string $intendedUse): self
    {
        $this->intendedUse = $intendedUse;

        return $this;
    }

    public function getIsPhotographyRequired(){
        return $this->isPhotographyRequired;
    }

    public function setIsPhotographyRequired($isPhotographyRequired): self
    {
        $this->isPhotographyRequired = $isPhotographyRequired;

        return $this;
    }

    public function getPublicationRequestType(): ?PublicationRequestType
    {
        return $this->publicationRequestType;
    }

    public function setPublicationRequestType(?PublicationRequestType $publicationRequestType): self
    {
        $this->publicationRequestType = $publicationRequestType;

        return $this;
    }
}
