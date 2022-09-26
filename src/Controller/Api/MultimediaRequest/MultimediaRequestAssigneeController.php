<?php

namespace App\Controller\Api\MultimediaRequest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\PersistentCollection;
use Hateoas\HateoasBuilder;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\View\View;
use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
use App\Entity\MultimediaRequest\MultimediaRequestAssigneeStatus;
use App\Service\MultimediaRequestService;

class MultimediaRequestAssigneeController extends AbstractFOSRestController
{

    private $service;

    public function __construct(MultimediaRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all multimedia request assignees
     *
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_VIEW')")
     * @Rest\Get("/multimediaassignees/{type}", defaults={"type" = null})
     */
    public function getMultimediaassigneesAction($type = null): Response
    {
        $filteredAssignees = array();
        $assignees = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->findBy([], ['lastName' => 'asc']);

        // If we are getting a certain type of assignee, get a list of only those assignees who have the request type attributed to them
        if ($type) {
            foreach($assignees as $assignee){
                if($assignee->hasAssignableRequestType($type)){
                    $filteredAssignees[] = $assignee;
                }
            }
        } else {
            $filteredAssignees = $assignees;
        }


        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($filteredAssignees, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get a single request assignee
     *
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_VIEW')")
     * @Rest\Get("/multimediaassignee/{id}")
     */
    public function getMultimediaassigneeAction($id): Response
    {
        $assignee = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->findOneBy(['id' => $id]);
        if (!$assignee) {
            $response = new Response("The multimedia request assginee was not found.", 404, array('Content-Type' => 'application/json'));
            return $response;
        }

        // Need to return NULL fields too (status doesn't have to have a value)
        // TUTORIAL: https://stackoverflow.com/questions/16784996/how-to-show-null-value-in-json-in-fos-rest-bundle-with-jms-serializer
        $context = new SerializationContext();
        $context->setSerializeNull(true);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($assignee, 'json', $context);
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get all status options for a multimedia request assignee
     *
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function getMultimediaassigneestatusesAction(): Response
    {
        $statuses = $this->getDoctrine()->getRepository(MultimediaRequestAssigneeStatus::class)->findBy([], ['statusSlug' => 'asc']);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($statuses, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Save a multimedia request assignee to the database
     *
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function postMultimediaassigneeAction(Request $request): Response
    {
        $assignee = new MultimediaRequestAssignee();

        $serializer = $this->container->get('jms_serializer');
        $em = $this->getDoctrine()->getManager();

        // set fields
        $assignee->setFirstName($request->request->get('firstName'));
        $assignee->setLastName($request->request->get('lastName'));
        $assignee->setEmail($request->request->get('email'));
        $assignee->setPhone($request->request->get('phone'));
        $assignee->setAssignableForRequestType($request->request->get('assignableRequestTypes'));
        // status
        $status = $this->getDoctrine()->getRepository(MultimediaRequestAssigneeStatus::class)->find($request->request->get('status')['id']);
        if ($status) {
            $assignee->setStatus($status);
        }

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
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function putMultimediaassigneeAction(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->container->get('jms_serializer');

        $assignee = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->find($request->request->get('id'));

        // set fields
        $assignee->setFirstName($request->request->get('firstName'));
        $assignee->setLastName($request->request->get('lastName'));
        $assignee->setEmail($request->request->get('email'));
        $assignee->setPhone($request->request->get('phone'));
        $assignee->setAssignableForRequestType($request->request->get('assignableRequestTypes'));
        // status
        $status = $this->getDoctrine()->getRepository(MultimediaRequestAssigneeStatus::class)->find($request->request->get('status')['id']);
        if ($status) {
            $assignee->setStatus($status);
        }

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
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function deleteMultimediaassigneeAction($id): Response
    {
        $assignee = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($assignee);
        $em->flush();

        $response = new Response('Assignee has been deleted.', 204, array('Content-Type' => 'application/json'));
        return $response;
    }
}
