<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Document;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ImageRepository")
 */
class Image extends Document
{
  /**
   * @ORM\Column(type="integer")
   */
  private $priority;

  public function setPriority($priority)
  {
      $this->priority = $priority;
  }

  public function getPriority()
  {
      return $this->$priority;
  }
}
