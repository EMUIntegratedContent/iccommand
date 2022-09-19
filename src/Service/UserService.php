<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\Entity\User;

class UserService
{
  private $container;
  private $em;

  public function __construct(EntityManagerInterface $em)
  {
    $this->container = new ContainerBuilder();
    $this->em = $em;
  }

  /**
   * Update user roles
   */
  public function syncUserRoles(User $user, $updatedRoles){
    $user->setRoles($updatedRoles);
    $this->em->persist($user);
    $this->em->flush();
  }

  /**
   * Toggle a user's status to enabled or disabled
   */
  public function setUserEnabledStatus(OldFosUser $user, $status){
    $userManager = $this->container->get('fos_user.user_manager');
    // Only change the status if the current and new status are different
    if($user->isEnabled() !== $status){
      $user->setEnabled($status);
      $userManager->updateUser($user);
    }
  }
}
