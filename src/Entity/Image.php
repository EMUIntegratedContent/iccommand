<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Document;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image extends Document
{

}
