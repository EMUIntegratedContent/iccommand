<?php

namespace App\Controller\Api\MultimediaRequest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\PersistentCollection;
use Hateoas\HateoasBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\View\View;
use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
use App\Entity\MultimediaRequest\MultimediaRequestAssigneeStatus;
use App\Service\MultimediaRequestService;
use Symfony\Component\Serializer\SerializerInterface;

class MultimediaRequestAssigneeController extends AbstractFOSRestController
{

    private MultimediaRequestService $service;
    private ManagerRegistry $doctrine;
    private SerializerInterface $serializer;
    private EntityManagerInterface $em;

    public function __construct(MultimediaRequestService $service, ManagerRegistry $doctrine, SerializerInterface $serializer, EntityManagerInterface $em)
    {
        $this->service = $service;
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
        $this->em = $em;
    }

    /**
     * Get all multimedia request assignees
     * @Rest\Get(path="/")
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_VIEW')")
     */
    public function getMultimediaassigneesAction(): Response
    {
        $assignees = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->findBy([], ['lastName' => 'asc']);

        $serialized = $this->serializer->serialize($assignees, 'json', ['groups' => 'multi']);
        return new Response($serialized, 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Get all multimedia request assignees
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_VIEW')")
     * @Rest\Get("/type/{type}", defaults={"type" = null})
     */
    public function getMultimediaassigneesByTypeAction($type): Response
    {
        $filteredAssignees = array();
        $assignees = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->findBy([], ['lastName' => 'asc']);

        // If we are getting a certain type of assignee, get a list of only those assignees who have the request type attributed to them
        if ($assignees) {
            foreach ($assignees as $assignee) {
                if ($assignee->hasAssignableRequestType($type)) {
                    $filteredAssignees[] = $assignee;
                }
            }
        }


        $serialized = $this->serializer->serialize($filteredAssignees, 'json', ['groups' => 'multi']);
        return new Response($serialized, 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Get all status options for a multimedia request assignee
     * @Rest\Get("/statuses")
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function getMultimediaassigneestatusesAction(): Response
    {
        $statuses = $this->doctrine->getRepository(MultimediaRequestAssigneeStatus::class)->findBy([], ['statusSlug' => 'asc']);

        $serialized = $this->serializer->serialize($statuses, 'json');
        return new Response($serialized, 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Get a single request assignee
     * @Rest\Get("/{id}")
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_VIEW')")
     */
    public function getMultimediaassigneeAction($id): Response
    {
        $assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->findOneBy(['id' => $id]);
        if (!$assignee) {
            return new Response("The multimedia request assignee was not found.", 404, array('Content-Type' => 'application/json'));
        }

        $serialized = $this->serializer->serialize($assignee, 'json', ['groups' => 'multi']);
        return new Response($serialized, 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Save a multimedia request assignee to the database
     * @Rest\Post()
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function postMultimediaassigneeAction(Request $request): Response
    {
        $assignee = new MultimediaRequestAssignee();

        // set fields
        $assignee->setFirstName($request->request->get('firstName'));
        $assignee->setLastName($request->request->get('lastName'));
        $assignee->setEmail($request->request->get('email'));
        $assignee->setPhone($request->request->get('phone'));
        $assignee->setAssignableForRequestType($request->get('assignableRequestTypes'));
        // status
        $status = $this->doctrine->getRepository(MultimediaRequestAssigneeStatus::class)->find($request->get('status')['id']);
        if ($status) {
            $assignee->setStatus($status);
        }

        // validate assignee
        $errors = $this->service->validate($assignee);
        if (count($errors) > 0) {
            $serialized = $this->serializer->serialize($errors, 'json');
            return new Response($serialized, 422, array('Content-Type' => 'application/json'));
        }

        // persist the assignee and commit
        $this->em->persist($assignee);
        $this->em->flush();

        $serialized = $this->serializer->serialize($assignee, 'json', ['groups' => 'multi']);
        return new Response($serialized, 201, array('Content-Type' => 'application/json'));
    }

    /**
     * Update a multimedia request assignee to the database
     * @Rest\Put()
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function putMultimediaassigneeAction(Request $request): Response
    {
        $assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($request->request->get('id'));

        // set fields
        $assignee->setFirstName($request->request->get('firstName'));
        $assignee->setLastName($request->request->get('lastName'));
        $assignee->setEmail($request->request->get('email'));
        $assignee->setPhone($request->request->get('phone'));
        $assignee->setAssignableForRequestType($request->get('assignableRequestTypes'));
        // status
        $status = $this->doctrine->getRepository(MultimediaRequestAssigneeStatus::class)->find($request->get('status')['id']);
        if ($status) {
            $assignee->setStatus($status);
        }

        // validate assignee
        $errors = $this->service->validate($assignee);
        if (count($errors) > 0) {
            $serialized = $this->serializer->serialize($errors, 'json');
            return new Response($serialized, 422, array('Content-Type' => 'application/json'));
        }

        // save the assignee
        $this->em->persist($assignee);
        $this->em->flush();

        $serialized = $this->serializer->serialize($assignee, 'json', ['groups' => 'multi']);
        return new Response($serialized, 201, array('Content-Type' => 'application/json'));
    }

    /**
     * Delete a multimedia request assignee from the database
     * @Rest\Delete(path="{id}")
     * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function deleteMultimediaassigneeAction($id): Response
    {
        $assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($id);

        $this->em->remove($assignee);
        $this->em->flush();

        return new Response('Assignee has been deleted.', 204, array('Content-Type' => 'application/json'));
    }
}
