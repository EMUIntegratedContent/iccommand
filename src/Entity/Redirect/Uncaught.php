<?php
namespace App\Entity\Redirect;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An uncaught link is a broken link that a large number of people have visited
 * and is recommended to be part of a redirect.
 * @ORM\Entity(repositoryClass="App\Repository\Redirect\UncaughtRepository")
 */
class Uncaught
{
  /* *************************** Member Variables *************************** */

  /**
   * @ORM\Id()
   * @ORM\GeneratedValue()
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255)
   */
  private $link;

  /**
   * The constructor of a redirect.
   */
  public function __construct() {

  }

  /* ******************************** Getters ******************************* */

  /**
   * Obtains the id of this redirect.
   * @return integer The id of this redirect.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Obtains the broken link that is being viisted multiple times.
   * @return string The broken link that is being visited multiple times.
   */
  public function getLink(): ?string {
    return $this->link;
  }
}
