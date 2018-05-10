<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequestAssigneeRepository")
 * @Serializer\XmlRoot("multimediaAssignee")
 */
class MultimediaRequestAssignee
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\XmlAttribute
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\SerializedName("firstName")
     * @Assert\NotBlank(message="You must provide a first name.")
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\SerializedName("lastName")
     * @Assert\NotBlank(message="You must provide a last name.")
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="You must provide a valid email address.")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="You must provide a status.")
     */
    private $status;

    /**
     * @ORM\Column(type="array")
     * @Serializer\SerializedName("assginableRequestTypes")
     */
    private $assignableForRequestType;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
    */
    private $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
    */
    private $updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    private $contentChanged;

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
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

    public function getAssignableForRequestType(): ?array
    {
        return $this->assignableForRequestType;
    }

    public function setAssignableForRequestType(array $assignableForRequestType): self
    {
        $this->assignableForRequestType = $assignableForRequestType;

        return $this;
    }

    /** GEDMO FIELDS **/

    public function getCreated(){
        return $this->created;
    }

    public function getUpdated(){
        return $this->updated;
    }

    public function getContentChanged(){
        return $this->contentChanged;
    }
}
