<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\Entity\User;

class UserService
{
  private $em;

  public function __construct(EntityManagerInterface $em)
  {
    $this->em = $em;
  }
}
