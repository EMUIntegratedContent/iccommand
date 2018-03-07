<?php

namespace App\Entity\Map;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Image;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Map\MapitemImageRepository")
 */
class MapitemImage extends Image
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
        return $this->priority;
    }
}
