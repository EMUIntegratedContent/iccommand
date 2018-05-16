<?php

namespace App\Entity\MultimediaRequest;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\MultimediaRequest\MultimediaRequest;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultimediaRequest\MultimediaRequestStatusNoteRepository")
 * @Serializer\XmlRoot("multimediaStatusNote")
 */
class MultimediaRequestStatusNote
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="You must provide text for this note.")
     */
    private $note;

    /**
     * Many notes can belong to one request.
     * @ORM\ManyToOne(targetEntity="MultimediaRequest", inversedBy="statusNotes")
     * @ORM\JoinColumn(name="multimedia_request_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\SerializedName("multimediaRequest")
     */
    private $multimediaRequest;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\Column(type="string", nullable=true)
     */
    private $createdBy;

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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getMultimediaRequest(): MultimediaRequest
    {
        return $this->multimediaRequest;
    }

    public function setMultimediaRequest(MultimediaRequest $multimediaRequest = null)
    {
        $this->multimediaRequest = $multimediaRequest;
    }

    /** GEDMO FIELDS **/
    public function getCreatedBy(){
      return $this->createdBy;
    }

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
