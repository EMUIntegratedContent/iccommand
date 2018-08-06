<?php
namespace App\Entity\Redirect;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A redirect is used to send someone from one link to another link.
 * @ORM\Entity(repositoryClass = "App\Repository\Redirect\RedirectRepository")
 * @UniqueEntity(
 *    fields = {"fromLink"},
 *    message = "The link has already been redirected to another link."
 * )
 */
class Redirect {
  /* *************************** Member Variables *************************** */

  /**
   * The link that would be entered in the URL bar.
   * @ORM\Column(
   *    type = "string",
   *    length = 255
   * )
   * @Serializer\SerializedName("fromLink")
   * @Assert\NotBlank(message = "You must provide the link that would be entered in the URL bar.")
   */
  private $fromLink;

  /**
   * The ID of this redirect.
   * @ORM\Id()
   * @ORM\GeneratedValue()
   * @ORM\Column(type = "integer")
   * @Serializer\XmlAttribute
   */
  private $id;

  /**
   * The item type of this redirect.
   * @ORM\Column(
   *    type = "string",
   *    length = 255
   * )
   * @Serializer\SerializedName("itemType")
   */
  private $itemType;

  /**
   * The time stamp when this redirect was last used.
   * @ORM\Column(
   *    type = "datetime",
   *    nullable = true
   * )
   * @Serializer\SerializedName("lastVisit")
   */
  private $lastVisit;

  /**
   * The link that one would be sent to.
   * @ORM\Column(
   *    type = "string",
   *    length = 255
   * )
   * @Serializer\SerializedName("toLink")
   * @Assert\NotBlank(message = "You must provide the actual link that one would be sent to.")
   */
  private $toLink;

  /**
   * The number of times users have used the $fromLink to go to the $toLink.
   * @ORM\Column(type = "integer")
   */
  private $visits;

  /* Gedmo Variables */

  /**
   * The time stamp when this redirect was created.
   * @ORM\Column(type = "datetime")
   * @Gedmo\Timestampable(on = "create")
   */
  private $created;

  /**
   * The user who created this redirect.
   * @ORM\Column(type = "string")
   * @Gedmo\Blameable(on = "create")
   * @Serializer\SerializedName("createdBy")
   */
  private $createdBy;

  /**
   * The time stamp when this redirect was last updated.
   * @ORM\Column(type = "datetime")
   * @Gedmo\Timestampable(on = "update")
   */
  private $updated;

  /**
   * The user who last updated this redirect.
   * @ORM\Column(type = "string")
   * @Gedmo\Blameable(on = "update")
   * @Serializer\SerializedName("updatedBy")
   */
  private $updatedBy;

  /**
   * The constructor of a redirect.
   */
  public function __construct() {

  }

  /* ************************** Getters and Setters ************************* */

  /**
   * Obtains the link that would be entered in the URL bar.
   * @return string The link that would be entered in the URL bar.
   */
  public function getFromLink(): ?string {
    return $this->fromLink;
  }

  /**
   * Obtains the ID of this redirect.
   * @return integer The ID of this redirect.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Obtains the item type of this redirect.
   * @return string The item type of this redirect.
   */
  public function getItemType(): ?string {
    return $this->itemType;
  }

  /**
   * Obtains the time stamp when this redirect was last used.
   * @return string The time stamp when this redirect was last used.
   */
  public function getLastVisit() {
    return $this->lastVisit;
  }

  /**
   * Obtains the link that one would be sent to.
   * @return string The link that one would be sent to.
   */
  public function getToLink(): ?string {
    return $this->toLink;
  }

  /**
   * Obtains the number of times users have used the $fromLink to go to the $toLink.
   * @return integer The number of times users have used the $fromLink to go to the $toLink.
   */
  public function getVisits(): int {
    return $this->visits;
  }

  /**
   * Changes the link that would be entered in the URL bar by the specified link.
   * @param string $fromLink The link that would be entered in the URL bar.
   * @return Redirect This redirect.
   */
  public function setFromLink(string $fromLink): self {
    $this->fromLink = $fromLink;

    return $this;
  }

  /**
   * Changes the item type of this redirect by the specified type.
   * @param string $itemType The item type of the redirect.
   * @return Redirect This redirect.
   */
  public function setItemType(string $itemType): self {
    $this->itemType = $itemType;

    return $this;
  }

  /**
   * Changes the time stamp when this redirect was last used.
   * @param string $lastVisit The time stamp when this redirect was last used.
   * @return Redirect This redirect.
   */
  public function setLastVisit(string $lastVisit): self {
    $this->lastVisit = $lastVisit;

    return $this;
  }

  /**
   * Changes the link that one would be sent to by the specified link.
   * @param string toLink The link that one would be sent to.
   * @return Redirect This redirect.
   */
  public function setToLink(string $toLink): self {
    $this->toLink = $toLink;

    return $this;
  }

  /**
   * Changes the number of times users have used the $fromLink to go to the $toLink.
   * @param int The number of times users have used the $fromLink to go to the $toLink.
   * @return Redirect This redirect.
   */
  public function setVisits(int $visits): self {
    $this->visits = $visits;

    return $this;
  }

  /* ***************************** Gedmo Getters **************************** */

  /**
   * Obtains the time stamp when this redirect was created.
   * @return datetime The time stamp when this redirect was created.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Obtains the user who created this redirect.
   * @return string The user who created this redirect.
   */
  public function getCreatedBy() {
    return $this->createdBy;
  }

  /**
   * Obtains the time stamp when this redirect was last updated.
   * @return datetime The time stamp when this redirect was last updated.
   */
  public function getUpdated() {
    return $this->updated;
  }

  /**
   * Obtains the user who last updated this redirect.
   * @return string The user who last updated this redirect.
   */
  public function getUpdatedBy() {
    return $this->updatedBy;
  }
}
