<?php
namespace App\Service;

use App\Entity\Redirect\Redirect;
use App\Enttiy\Redirect\Uncaught;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The redirect service is used primary for deleting redirects, getting redirect
 * permissions of users, and validating.
 */
class RedirectService {
  private $container;
  private $authorizationChecker;
  private $validator;
  private $doctrine;
  private $em;

  /**
   * The constructor of the service of the redirects.
   */
  public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em) {
    $this->container = new ContainerBuilder();
    $this->authorizationChecker = $authorizationChecker;
    $this->validator = $validator;
    $this->doctrine = $doctrine;
    $this->em = $em;
  }

  /**
   * Removes a redirect from the database.
   * @param Redirect $redirect The redirect to be removed.
   */
  public function deleteRedirect(Redirect $redirect): void
  {
    $this->em->remove($redirect);
    $this->em->flush();
  }

  /**
   * Fetches the permissions of the user for managing redirects.
   * @return array The user's permissions for managing redirects.
   */
  #[ArrayShape(['user' => "bool", 'admin' => "bool"])]
  public function getUserRedirectPermissions(): array
  {
    // Set all permissions to false as default.
    $redirectPermissions = array(
      'user' => false,
      'admin' => false
    );

    // The admins automatically have all the permissions.
    if ($this->authorizationChecker->isGranted('ROLE_REDIRECT_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
      $redirectPermissions['user'] = true;
      $redirectPermissions['admin'] = true;
    }

    // The non-admins have the "user" permission.
    if ($this->authorizationChecker->isGranted('ROLE_REDIRECT_USER')) {
      $redirectPermissions['user'] = true;
    }

    return $redirectPermissions;
  }

    /**
     * Uses the Symfony container's validator to validate fields for a redirect.
     * @param Redirect A redirect that makes one link go to another link.
     * @return ConstraintViolationList A list of errors.
     */
  public function validate($redirect): ConstraintViolationList {
    return $this->validator->validate($redirect);
  }
}
