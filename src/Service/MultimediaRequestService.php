<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\PersistentCollection;
use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
//use App\Entity\MultimediaRequest\MultimediaRequest;

class MultimediaRequestService
{
  private $container;

  public function __construct(Container $container)
  {
    $this->container = $container;
  }

  /**
   * Use the Symfony container's validator to validate fields for an assignee
   * @param App\Entity\MultimediaRequest\MultimediaRequestAssignee $assignee
   * @return array $errors
   */
  public function validate($assignee) : ConstraintViolationList
  {
    $validator = $this->container->get('validator');
    $errors = $validator->validate($assignee);
    return $errors;
  }

  /**
   *  Fetch the user's permissions for managing photo requests
   *  @return array $multimediaRequestPermissions
   */
  public function getUserMultimediaRequestPermissions(){
    $multimediaRequestPermissions = array(
      'create' => false,
      'edit' => false,
      'delete' => false,
      'admin' => false
    );
    if($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('ROLE_GLOBAL_ADMIN')){
      $multimediaRequestPermissions['create'] = true;
      $multimediaRequestPermissions['edit'] = true;
      $multimediaRequestPermissions['delete'] = true;
      $multimediaRequestPermissions['admin'] = true;
    }
    if($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_DELETE')){
      $multimediaRequestPermissions['delete'] = true;
      $multimediaRequestPermissions['edit'] = true;
    }
    if($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_EDIT')){
      $multimediaRequestPermissions['create'] = true;
      $multimediaRequestPermissions['edit'] = true;
    }
    if($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_CREATE')){
      $multimediaRequestPermissions['create'] = true;
    }
    return $multimediaRequestPermissions;
  }

/*
  protected function deleteMultimediaRequest(MultimediaRequest $multimediaRequest)
  {
    $em = $this->container->get('doctrine')->getManager();
    $em->remove($photoRequest);
    $em->flush();
  }
*/
}
