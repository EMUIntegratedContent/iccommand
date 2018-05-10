<?php
namespace App\Controller\Api\MultimediaRequest;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\PersistentCollection;
use Hateoas\HateoasBuilder;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\View\View;
use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
use App\Service\MultimediaRequestService;

class MultimediaRequestAssigneeController extends FOSRestController{

  private $service;

  public function __construct(MultimediaRequestService $service){
    $this->service = $service;
  }

  /**
   * Get all multimedia request assignees
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
   */
  public function getMultimediaassigneesAction() : Response
  {
    $assignees = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->findBy([],['name' => 'asc']);

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($assignees, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Get a single request assignee
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
   */
  public function getMultimediaassigneeAction($id) : Response
  {
    $assignee = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->findOneBy(['id' => $id]);
    if(!$assignee){
      $response = new Response("The multimedia request assginee was not found.", 404, array('Content-Type' => 'application/json'));
      return $response;
    }

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($assignee, 'json', $context);
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }


  /**
   * Save a multimedia request assignee to the database
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_ADMIN')")
   */
  public function postMultimediaassigneeAction(Request $request) : Response
  {
    $assignee = new MultimediaRequestAssignee();

    $serializer = $this->container->get('jms_serializer');
    $em = $this->getDoctrine()->getManager();

    // set fields
    $assignee->setFirstName($request->request->get('firstName'));
    $assignee->setLastName($request->request->get('lastName'));
    $assignee->setEmail($request->request->get('email'));
    $assignee->setPhone($request->request->get('phone'));
    $assignee->setStatus($request->request->get('status'));
    $assignee->setAssignableForRequestType($request->request->get('assginableRequestTypes'));

    // validate assignee
    $errors = $this->service->validate($assignee);
    if (count($errors) > 0) {
        $serialized = $serializer->serialize($errors, 'json');
        $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));

        return $response;
    }

    // persist the assignee and commit
    $em->persist($assignee);
    $em->flush();

    $serialized = $serializer->serialize($assignee, 'json');
    $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
    return $response;
  }

  /**
   * Update a multimedia request assignee to the database
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_ADMIN')")
   */
  public function putMultimediaassigneeAction(Request $request) : Response
  {
    $em = $this->getDoctrine()->getManager();
    $serializer = $this->container->get('jms_serializer');

    $assignee = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->find($request->request->get('id'));

    // set fields
    $assignee->setFirstName($request->request->get('firstName'));
    $assignee->setLastName($request->request->get('lastName'));
    $assignee->setEmail($request->request->get('email'));
    $assignee->setPhone($request->request->get('phone'));
    $assignee->setStatus($request->request->get('status'));
    $assignee->setAssignableForRequestType($request->request->get('assginableRequestTypes'));

    // validate assignee
    $errors = $this->service->validate($assignee);
    if (count($errors) > 0) {
        $serialized = $serializer->serialize($errors, 'json');
        $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
        return $response;
    }

    // save the assignee
    $em->persist($assignee);
    $em->flush();

    $serialized = $serializer->serialize($assignee, 'json');
    $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
    return $response;
  }

  /**
   * Delete a multimedia request assignee from the database
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_ADMIN')")
   */
  public function deleteMultimediaassigneeAction($id) : Response
  {
    $assignee = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->find($id);

    $em = $this->getDoctrine()->getManager();
    $em->remove($assignee);
    $em->flush();

    $response = new Response('Assignee has been deleted.', 204, array('Content-Type' => 'application/json'));
    return $response;
  }
}
