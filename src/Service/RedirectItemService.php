<?php
namespace App\Service;

use App\Entity\Redirect\Redirect;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Validator\ConstraintViolationList;

class RedirectItemService {
  private $container;

  /**
   * The constructor of the redirect service.
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
   * @param App\Entity\Redirect
   * @return array A list of errors.
   */
  public function validate($mapItem): ConstraintViolationList {
    $validator = $this->container->get('validator');
    $errors = $validator->validate($mapItem);
    return $errors;
  }
}
