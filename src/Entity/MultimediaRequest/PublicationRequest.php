<?php
// NO LONGER IN USE AS OF 2025 (hadn't been since 2018) - kept for reference
namespace App\Entity\MultimediaRequest;

//use Doctrine\ORM\Mapping as ORM;
//use Symfony\Component\Validator\Constraints as Assert;
//use Symfony\Component\Serializer\Annotation\SerializedName;
//use App\Entity\MultimediaRequest\PublicationRequestType;
//use Symfony\Component\Serializer\Annotation\Groups;
//
//#[ORM\Entity(repositoryClass: "App\Repository\MultimediaRequest\PublicationRequestRepository")]
class PublicationRequest extends MultimediaRequest
{
//	const REQUEST_TYPE = 'publication';
//
//	#[ORM\Column(type: "date")]
//	#[Assert\DateTime(message: "You must provide a valid completion date.")]
//	#[SerializedName("completionDate")]
//	#[Groups("multi")]
//	private $completionDate;
//
//	#[ORM\Column(type: "string", length: 20)]
//	#[Assert\Length(
//		max: 20,
//		maxMessage: "Intended use cannot be longer than {{ limit }} characters"
//	)]
//	#[SerializedName("intendedUse")]
//	#[Groups("multi")]
//	private $intendedUse;
//
//	#[ORM\Column(type: "boolean")]
//	#[Assert\NotNull(message: "Is photography required for this request?")]
//	#[SerializedName("isPhotographyRequired")]
//	#[Groups("multi")]
//	private $isPhotographyRequired;
//
//	#[ORM\ManyToOne(targetEntity: "PublicationRequestType")]
//	#[SerializedName("publicationRequestType")]
//	#[Groups("multi")]
//	private $publicationRequestType;
//
//	public function __construct()
//	{
//		parent::__construct();
//	}
//
//	#[SerializedName("requestType")]
//	#[Groups("multi")]
//	public function getRequestType(): string
//	{
//		return constant("self::REQUEST_TYPE");
//	}
//
//	public function getCompletionDate(): ?\DateTimeInterface
//	{
//		return $this->completionDate;
//	}
//
//	public function setCompletionDate(\DateTimeInterface $completionDate): self
//	{
//		$this->completionDate = $completionDate;
//		return $this;
//	}
//
//	public function getIntendedUse(): ?string
//	{
//		return $this->intendedUse;
//	}
//
//	public function setIntendedUse(string $intendedUse): self
//	{
//		$this->intendedUse = $intendedUse;
//		return $this;
//	}
//
//	public function getIsPhotographyRequired()
//	{
//		return $this->isPhotographyRequired;
//	}
//
//	public function setIsPhotographyRequired($isPhotographyRequired): self
//	{
//		$this->isPhotographyRequired = $isPhotographyRequired;
//		return $this;
//	}
//
//	public function getPublicationRequestType(): ?PublicationRequestType
//	{
//		return $this->publicationRequestType;
//	}
//
//	public function setPublicationRequestType(?PublicationRequestType $publicationRequestType): self
//	{
//		$this->publicationRequestType = $publicationRequestType;
//		return $this;
//	}
}
