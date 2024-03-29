<?php
namespace App\Controller\Api\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\View\View;
use App\Entity\User;
use App\Service\UserService;


class UserController extends AbstractFOSRestController{

  private UserService $service;
  private SerializerInterface $serializer;
  private ManagerRegistry $doctrine;
  private EntityManagerInterface $em;

  public function __construct(UserService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em){
    $this->service = $service;
    $this->serializer = $serializer;
    $this->doctrine = $doctrine;
    $this->em = $em;
  }

  /**
   * Get all users
   * @Rest\Get (path="/users")
   * @Security("is_granted('ROLE_GLOBAL_ADMIN')")
   */
  public function getUsersAction() : Response
  {
      $userRepo = $this->em->getRepository(User::class);
      $users = $userRepo->findAll();
      $view = $this->view($users, 200);

      return $this->handleView($view);
  }

  /**
   * Return an individual user (by username)
   * @Rest\Get(path="/users/{username}")
   * @Security("is_granted('ROLE_USER')")
   */
  public function getUserAction($username) : Response
  {
    $user = $this->doctrine->getRepository(User::class)->findOneByUsername($username);

    $serialized = $this->serializer->serialize($user, 'json');
    return new Response($serialized, 200, array('Content-Type' => 'application/json'));
  }

  /**
   * Return all defined roles
   * @Rest\Get(path="/roles")
   * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MAP_ADMIN')")
   */
  public function getRolesAction() : Response
  {
    $roles = $this->getParameter('security.role_hierarchy.roles');

    $serialized = $this->serializer->serialize($roles, 'json');
    return new Response($serialized, 200, array('Content-Type' => 'application/json'));
  }

    /**
     * Update a user's information and roles
     * @Rest\Put(path="/users/{username}")
     * @param Request $request
     * @param String $username
     * @return Response
     * @Security("is_granted('ROLE_USER')")
     */
   public function putUserAction(Request $request, string $username) : Response
   {
     $user = $this->doctrine->getRepository(User::class)->findOneByUsername($username);

     if(!$user){
       throw $this->createNotFoundException('The user ' . $username . ' was not found.');
     }

     $user->setFirstName($request->request->get('firstName'));
     $user->setLastName($request->request->get('lastName'));
     $user->setJobTitle($request->request->get('jobTitle'));
     $user->setDepartment($request->request->get('department'));
     $user->setPhone($request->request->get('phone'));
     $user->setRoles($request->get('roles'));
     $user->setEnabled($request->request->get('enabled'));
      $this->em->persist($user);
      $this->em->flush();

     return new Response('User ' . $username . ' was updated successfully.', 200, array('Content-Type' => 'application/json'));
   }

   /**
    * Return all users of an application
    * @Rest\Get(path="/appusers/{rolePrefix}")
    * @param String $rolePrefix (e.g. the role prefix for map app users is 'ROLE_MAP_')
    * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MAP_ADMIN')")
    */
   public function getAppusersAction(string $rolePrefix) : Response
   {
     $mapAppUsers = $this->doctrine->getRepository(User::class)->findByLikeRole($rolePrefix);

     $serialized = $this->serializer->serialize($mapAppUsers, 'json');
     return new Response($serialized, 200, array('Content-Type' => 'application/json'));
   }

   /**
    * Return all users that are NOT part of an application
    * @Rest\Get(path="/appusers/not/{rolePrefix}")
    * @param String $rolePrefix (e.g. the role prefix for map app users is 'ROLE_MAP_')
    * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MAP_ADMIN')")
    */
   public function getAppusersNotAction(string $rolePrefix) : Response
   {
     $mapAppUsers = $this->doctrine->getRepository(User::class)->findByLikeRole($rolePrefix, true);

     $serialized = $this->serializer->serialize($mapAppUsers, 'json');
     return new Response($serialized, 200, array('Content-Type' => 'application/json'));
   }
}
