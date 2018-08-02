<?php
namespace App\Entity\Redirect;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * An uncaught link is a broken link that a large number of users have visited
 * and is recommended to be part of a redirect.
 * @ORM\Entity(repositoryClass = "App\Repository\Redirect\UncaughtRepository")
 * @UniqueEntity("link")
 */
class Uncaught {
  /* *************************** Member Variables *************************** */

  /**
   * The ID of this uncaught item.
   * @ORM\Id()
   * @ORM\GeneratedValue()
   * @ORM\Column(type = "integer")
   * @Serializer\XmlAttribute
   */
  private $id;

  /**
   * Determines if this uncaught item should be recommended as a redirect.
   * @ORM\Column(type = "boolean")
   * @Serializer\SerializedName("isRecommended")
   */
  private $isRecommended = true;

  /**
   * The broken link that is being visited multiple times.
   * @ORM\Column(
   *    type="string",
   *    length=191,
   *    unique=true,
   * )
   */
  private $link;

  /**
   * The number of times users have used the broken link.
   * @ORM\Column(type = "integer")
   */
  private $visits;

  /**
   * The constructor of an uncaught item.
   */
  public function __construct() {

  }

  /* ************************** Getters and Setters ************************* */

  /**
   * Obtains the ID of this uncaught item.
   * @return integer The ID of this uncaught item.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Obtains the boolean value that determines if this uncaught item should be
   * recommended as a redirect.
   * @return boolean True if this uncaught item should be recommended as a
   * redirect; false otherwise.
   */
  public function getIsRecommended() {
    return $this->isRecommended;
  }

  /**
   * Obtains the broken link that is being visited multiple times.
   * @return string The broken link that is being visited multiple times.
   */
  public function getLink(): ?string {
    return $this->link;
  }

  /**
   * Obtains the number of times users have used the broken link.
   * @return integer The number of times users have used the broken link.
   */
  public function getVisits(): int {
    return $this->visits;
  }

  /**
   * Changes the boolean value that determines if this uncaught item should be
   * recommended as a redirect.
   * @param boolean The boolean value that determines if this uncaught item
   * should be recommended as a redirect.
   * @return Uncaught This uncaught item.
   */
  public function setIsRecommended($isRecommended): self {
    $this->isRecommended = $isRecommended;

    return $this;
  }

  /**
   * Changes the broken link that is being visited multiple times by the
   * specified link.
   * @param string $link The broken link that is being visited multiple times.
   * @return Uncaught This uncaught item.
   */
  public function setLink(string $link): self {
    $this->link = $link;

    return $this;
  }

  /**
   * Changes the number of times users have used the broken link.
   * @param integer The number of times users have used the broken link.
   * @return Uncaught This uncaught item.
   */
  public function setVisits(int $visits): self {
    $this->visits = $visits;

    return $this;
  }
}
