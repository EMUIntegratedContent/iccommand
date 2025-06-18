<?php

namespace App\Service;

use App\Entity\CrimeLog\CrimeLog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\PseudoTypes\ArrayShape;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The crimelog service service is used primary for deleting crimelogs, getting crimelog
 * permissions of users, and validating.
 */
class CrimeLogService
{
  private AuthorizationCheckerInterface $authorizationChecker;
  private ValidatorInterface $validator;
  private ManagerRegistry $doctrine;
  private EntityManagerInterface $em;

  /**
   * The constructor of the service of the crimelogs.
   */
  public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em)
  {
    $this->authorizationChecker = $authorizationChecker;
    $this->validator = $validator;
    $this->doctrine = $doctrine;
    $this->em = $em;
  }

  /**
   * Removes a crimelog from the database.
   * @param CrimeLog $crimelog The redirect to be removed.
   */
  public function deleteCrimeLog(CrimeLog $crimelog): void
  {
    $this->em->remove($crimelog);
    $this->em->flush();
  }

  /**
   * Fetches the permissions of the user for managing crimelogs.
   * @return array The user's permissions for managing crimelogs.
   */
  #[ArrayShape(['user' => "bool", 'admin' => "bool"])]
  public function getUserCrimeLogPermissions(): array
  {
    // Set all permissions to false as default.
    $crimelogPermissions = array(
      'user' => false,
      'admin' => false
    );

    // The admins automatically have all the permissions.
    if ($this->authorizationChecker->isGranted('ROLE_REDIRECT_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
      $crimelogPermissions['user'] = true;
      $crimelogPermissions['admin'] = true;
    }

    // The non-admins have the "user" permission.
    if ($this->authorizationChecker->isGranted('ROLE_REDIRECT_USER')) {
      $crimelogPermissions['user'] = true;
    }

    return $crimelogPermissions;
  }
}
