<?php
// NO LONGER IN USE AS OF 2025 (hadn't been since 2018) - kept for reference
namespace App\Entity\MultimediaRequest;

//use Doctrine\ORM\Mapping as ORM;
//use Symfony\Component\Validator\Constraints as Assert;
//use Symfony\Component\Serializer\Annotation\SerializedName;
//use Symfony\Component\Serializer\Annotation\Groups;
//
//#[ORM\Entity(repositoryClass: "App\Repository\MultimediaRequest\VideoRequestRepository")]
class VideoRequest extends MultimediaRequest
{
//	const REQUEST_TYPE = 'video';
//
//	#[ORM\Column(type: "date")]
//	#[Assert\Type(\DateTimeInterface::class, message: "You must provide a valid completion date.")]
//	#[SerializedName("completionDate")]
//	#[Groups("multi")]
//	private \DateTimeInterface $completionDate;
//
//	public function __construct() {
//		parent::__construct();
//	}
//
//	#[SerializedName("requestType")]
//	#[Groups("multi")]
//	public function getRequestType(): string {
//		return self::REQUEST_TYPE;
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
//
//		return $this;
//	}
}
