<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Image;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserImageRepository")
 */
class UserImage extends Image
{
  
}
