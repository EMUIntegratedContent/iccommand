<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\Entity\OldFosUser;

class UserService
{
  private $container;

  public function __construct()
  {
    $this->container = new ContainerBuilder();
  }

  /**
   * Add/remove user roles by comparing currently-stored roles to newly-submitted roles
   */
  public function syncUserRoles(OldFosUser $user, $updatedRoles){
    $userManager = $this->container->get('fos_user.user_manager');
    $currentRoles = $user->getRoles();

    $rolesToRemove = array_diff($currentRoles, $updatedRoles); // result is the items that do NOT appear in the updated roles array

    // Find and remove any roles that do NOT appear in the updated roles
    foreach($rolesToRemove as $role){
      $user->removeRole($role);
    }
    // Add roles (must be done second!)
    foreach($updatedRoles as $role){
      $user->addRole($role);
    }
    $userManager->updateUser($user);
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
