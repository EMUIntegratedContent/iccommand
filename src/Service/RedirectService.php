<?php
namespace App\Service;

use App\Entity\Redirect\Redirect;
use App\Enttiy\Redirect\Uncaught;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * The redirect service is used primary for deleting redirects, getting redirect
 * permissions of users, and validating.
 */
class RedirectService {
  private $container;

  /**
   * The constructor of the service of the redirects.
   * @param Container $container The container for the service of the redirects.
   */
  public function __construct(Container $container) {
    $this->container = $container;
  }

  /**
   * Removes a redirect from the database.
   * @param Redirect $redirect The redirect to be removed.
   */
  public function deleteRedirect(Redirect $redirect) {
    $manager = $this->container->get('doctrine')->getManager();
    $manager->remove($redirect);
    $manager->flush();
  }

  /**
   * Fetches the permissions of the user for managing redirects.
   * @return array The user's permissions for managing redirects.
   */
  public function getUserRedirectPermissions() {
    // Set all permissions to false as default.
    $redirectPermissions = array(
      'user' => false,
      'admin' => false
    );

    // The admins automatically have all the permissions.
    if ($this->container->get('security.authorization_checker')->isGranted('ROLE_REDIRECT_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('ROLE_GLOBAL_ADMIN')) {
      $redirectPermissions['user'] = true;
      $redirectPermissions['admin'] = true;
    }

    // The non-admins have the "user" permission.
    if ($this->container->get('security.authorization_checker')->isGranted('ROLE_REDIRECT_USER')) {
      $redirectPermissions['user'] = true;
    }

    return $redirectPermissions;
  }

  /**
   * Uses the Symfony container's validator to validate fields for a redirect.
   * @param Redirect A redirect that makes one link go to another link.
   * @return array A list of errors.
   */
  public function validate($redirect): ConstraintViolationList {
    $validator = $this->container->get('validator');
    $errors = $validator->validate($redirect);

    return $errors;
  }
}
