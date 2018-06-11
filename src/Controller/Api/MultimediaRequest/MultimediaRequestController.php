<?php

namespace App\Controller\Api\MultimediaRequest;

use App\Entity\MultimediaRequest\HeadshotRequest;
use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
use App\Entity\MultimediaRequest\MultimediaRequestStatusNote;
use App\Entity\MultimediaRequest\PhotoHeadshotDate;
use App\Entity\MultimediaRequest\PhotoRequest;
use App\Entity\MultimediaRequest\PhotoRequestType;
use App\Entity\MultimediaRequest\PublicationRequest;
use App\Entity\MultimediaRequest\PublicationRequestType;
use App\Entity\MultimediaRequest\VideoRequest;
use App\Entity\MultimediaRequest\MultimediaRequest;
use App\Entity\MultimediaRequest\MultimediaRequestStatus;
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
use App\Service\MultimediaRequestService;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MultimediaRequestController extends FOSRestController
{

    private $service;

    public function __construct(MultimediaRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * EXTERNAL API ENDPOINT. Get headshot time slots.
     * @return Response
     */
    public function getExternalMultimediarequestHeadshotdatesAction()
    {
        // Get all future headshot time slots (query in repository).
        $futureTimeSlots = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->findByFutureDateTime();
        if (!$futureTimeSlots) {
            throw $this->createNotFoundException('No future time slots were found.');
        }

        $serializer = $this->container->get('jms_serializer');

        $serialized = $serializer->serialize($futureTimeSlots, 'json');
        $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
        return $response;
    }

    /**
     * EXTERNAL API ENDPOINT. Get publication types.
     * @return Response
     */
    public function getExternalMultimediarequestPublicationtypesAction()
    {
        $types = $this->getDoctrine()->getRepository(PublicationRequestType::class)->findBy([], ['slug' => 'asc']);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($types, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * EXTERNAL API ENDPOINT. Handle new multimedia request.
     * @Rest\Post("external/multimediarequests")
     * @return Response
     */
    public function postExternalMultimediarequestAction(Request $request)
    {
        $mmRequest = null;

        $serializer = $this->container->get('jms_serializer');
        $em = $this->getDoctrine()->getManager();

        // Determine which type of Multimedia Request to create based on type & set type-specific fields
        switch ($request->request->get('requestType')) {
            case 'headshot':
                $mmRequest = new HeadshotRequest();

                // Get the time slot
                $timeSlot = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->find($request->request->get('headshotTimeSlot'));
                if ($timeSlot) {
                    $mmRequest->setTimeSlot($timeSlot);
                }
                $mmRequest->setDescription($request->request->get('description'));
                break;
            case 'photo':
                $mmRequest = new PhotoRequest();

                // Get the photo request type
                $photoRequestType = $this->getDoctrine()->getRepository(PhotoRequestType::class)->findOneBy(['slug' => $request->request->get('photoRequestType')]);
                if (!$photoRequestType) {
                    throw new HttpException(400, "An invalid photo request type was passed");
                }
                $mmRequest->setPhotoRequestType($photoRequestType);
                $mmRequest->setStartTime(\DateTime::createFromFormat('Y-m-d H:i:s', $request->request->get('startTime')));
                $mmRequest->setEndTime(\DateTime::createFromFormat('Y-m-d H:i:s', $request->request->get('endTime')));
                $mmRequest->setLocation($request->request->get('location'));
                $mmRequest->setIntendedUse($request->request->get('intendedUse'));
                $mmRequest->setDescription($request->request->get('description'));

                break;
            case 'video':
                $mmRequest = new VideoRequest();
                $mmRequest->setCompletionDate(\DateTime::createFromFormat('Y-m-d', $request->request->get('completionDate')));
                $mmRequest->setDescription($request->request->get('description'));
                break;
            case 'publication':
                $mmRequest = new PublicationRequest();

                // Get the publication request type
                $publicationRequestType = $this->getDoctrine()->getRepository(PublicationRequestType::class)->find($request->request->get('publicationRequestType'));
                if (!$publicationRequestType) {
                    throw new HttpException(400, "An invalid publication request type was passed");
                }
                $mmRequest->setPublicationRequestType($publicationRequestType);
                $mmRequest->setIntendedUse($request->request->get('intendedUse'));
                $mmRequest->setCompletionDate(\DateTime::createFromFormat('Y-m-d', $request->request->get('completionDate')));
                $mmRequest->setDescription($request->request->get('description'));
                $mmRequest->setIsPhotographyRequired($request->request->get('isPhotographyRequired'));
                break;
            default:
                throw $this->createNotFoundException('You passed an invalid request type.');
        }

        // set common fields
        $mmRequest->setFirstName($request->request->get('firstName'));
        $mmRequest->setLastName($request->request->get('lastName'));
        $mmRequest->setEmail($request->request->get('email'));
        $mmRequest->setPhone($request->request->get('phone'));
        $mmRequest->setDepartment($request->request->get('department'));

        // set request status to 'new'
        $status = $this->getDoctrine()->getRepository(MultimediaRequestStatus::class)->findOneBy(['statusSlug' => 'new']);
        if ($status) {
            $mmRequest->setStatus($status);
        }

        // validate request
        $errors = $this->service->validate($mmRequest);
        if (count($errors) > 0) {
            $serialized = $serializer->serialize($errors, 'json');
            $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));

            return $response;
        }

        // persist the assignee and commit
        $em->persist($mmRequest);
        $em->flush();

        $serialized = $serializer->serialize($mmRequest, 'json');
        $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
        return $response;
    }

    /**
     * Get all multimedia requests (by optional type)
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
     * @Rest\Get("multimediarequests/list/{type?}")
     * @param String $type Photo, video, publication, headshot, etc.
     * @return Response
     */
    public function getMultimediarequestsListAction($type = null): Response
    {
        $mmRequests = null;
        switch ($type) {
            case 'headshot':
                $mmRequests = $this->getDoctrine()->getRepository(HeadshotRequest::class)->findBy([], ['created' => 'asc']);
                break;
            case 'photo':
                $mmRequests = $this->getDoctrine()->getRepository(PhotoRequest::class)->findBy([], ['created' => 'asc']);
                break;
            case 'video':
                $mmRequests = $this->getDoctrine()->getRepository(VideoRequest::class)->findBy([], ['created' => 'asc']);
                break;
            case 'publication':
                $mmRequests = $this->getDoctrine()->getRepository(PublicationRequest::class)->findBy([], ['created' => 'asc']);
                break;
            default:
                $mmRequests = $this->getDoctrine()->getRepository(MultimediaRequest::class)->findBy([], ['created' => 'asc']);
                break;
        }

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($mmRequests, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get a single request
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
     */
    public function getMultimediarequestAction($id): Response
    {
        $mmRequest = $this->getDoctrine()->getRepository(MultimediaRequest::class)->findOneBy(['id' => $id]);
        if (!$mmRequest) {
            $response = new Response("The multimedia request was not found.", 404, array('Content-Type' => 'application/json'));
            return $response;
        }

        // Need to return NULL fields too (status doesn't have to have a value)
        // TUTORIAL: https://stackoverflow.com/questions/16784996/how-to-show-null-value-in-json-in-fos-rest-bundle-with-jms-serializer
        $context = new SerializationContext();
        $context->setSerializeNull(true);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($mmRequest, 'json', $context);
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get all photo request types
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
     */
    public function getPhotorequestTypesAction(): Response
    {
        $types = $this->getDoctrine()->getRepository(PhotoRequestType::class)->findBy([], ['slug' => 'asc']);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($types, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get all publication request types
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
     */
    public function getPublicationrequestTypesAction(): Response
    {
        $types = $this->getDoctrine()->getRepository(PublicationRequestType::class)->findBy([], ['slug' => 'asc']);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($types, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get all status options for a multimedia request
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
     */
    public function getMultimediarequestStatusesAction(): Response
    {
        $statuses = $this->getDoctrine()->getRepository(MultimediaRequestStatus::class)->findBy([], ['statusSlug' => 'asc']);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($statuses, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get headshot time slots for a specific date
     *
     * @Rest\Get("multimediarequests/headshotdates/{year}/{month}/{day}", defaults={})
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
     */
    public function getMultimediarequestHeadshotdatesAction($year, $month, $day): Response
    {
        $dateOfShoot = Carbon::parse($year . '-' . $month . '-' . $day);
        $timeSlots = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->findBy(['dateOfShoot' => $dateOfShoot], ['startTime' => 'asc']);

        $serializer = $this->container->get('jms_serializer');
        $serialized = $serializer->serialize($timeSlots, 'json');
        $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

        return $response;
    }

    /**
     * Get headshot time slots
     * @Rest\Get("multimediarequests/headshotdates/slots/{withPast?}")
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_VIEW')")
     * @param boolean $withPast  If true, get past time slots. If false, just get future slots
     * @return Response
     */
    public function getMultimediarequestHeadshottimeslotsAction($withPast = false)
    {
        // Get all future headshot time slots (query in repository).
        if($withPast){
            $timeSlots = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->findBy([], ['dateOfShoot', 'ASC']);
        } else {
            $timeSlots = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->findByFutureDateTime();
        }

        if (!$timeSlots) {
            throw $this->createNotFoundException('No time slots were found.');
        }

        $serializer = $this->container->get('jms_serializer');

        $serialized = $serializer->serialize($timeSlots, 'json');
        $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
        return $response;
    }

    /**
     * Add a headshot time slot to the database
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function postMultimediarequestHeadshotdateAction(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->container->get('jms_serializer');

        $headshotDate = new PhotoHeadshotDate();

        $headshotDate->setDateOfShoot(\DateTime::createFromFormat('Y-m-d', $request->request->get('dateOfShoot')));
        $headshotDate->setStartTime(\DateTime::createFromFormat('g:i a', $request->request->get('startTime')));
        $headshotDate->setEndTime(\DateTime::createFromFormat('g:i a', $request->request->get('endTime')));

        // validate request
        $errors = $this->service->validate($headshotDate);
        if (count($errors) > 0) {
            $serialized = $serializer->serialize($errors, 'json');
            $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
            return $response;
        }

        // save the assignee
        $em->persist($headshotDate);
        $em->flush();

        $serialized = $serializer->serialize($headshotDate, 'json');
        $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
        return $response;
    }

    /**
     * Update a multimedia request to the database
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_EDIT')")
     */
    public function putMultimediarequestAction(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->container->get('jms_serializer');

        $mmRequest = $this->getDoctrine()->getRepository(MultimediaRequest::class)->find($request->request->get('id'));

        // Determine which type of Multimedia Request to create based on type & set type-specific fields
        switch ($request->request->get('discr')) {
            case 'headshotrequest':
                // Get the time slot
                $timeSlot = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->find($request->request->get('timeSlot')['id']);
                if ($timeSlot) {
                    $mmRequest->setTimeSlot($timeSlot);
                }
                $mmRequest->setDescription($request->request->get('description'));
                break;
            case 'photorequest':
                // Get the photo request type
                $photoRequestType = $this->getDoctrine()->getRepository(PhotoRequestType::class)->findOneBy(['slug' => $request->request->get('photoRequestType')]);
                if (!$photoRequestType) {
                    throw new HttpException(400, "An invalid photo request type was passed");
                }
                $mmRequest->setPhotoRequestType($photoRequestType);
                $mmRequest->setStartTime(\DateTime::createFromFormat('Y-m-d H:i:s', $request->request->get('startTime')));
                $mmRequest->setEndTime(\DateTime::createFromFormat('Y-m-d H:i:s', $request->request->get('endTime')));
                $mmRequest->setLocation($request->request->get('location'));
                $mmRequest->setIntendedUse($request->request->get('intendedUse'));
                $mmRequest->setDescription($request->request->get('description'));
                break;
            case 'videorequest':
                $mmRequest->setCompletionDate(\DateTime::createFromFormat('Y-m-d', $request->request->get('completionDate')));
                $mmRequest->setDescription($request->request->get('description'));
                break;
            case 'publicationrequest':
                // Get the publication request type
                $publicationRequestType = $this->getDoctrine()->getRepository(PublicationRequestType::class)->findOneBy(['slug' => $request->request->get('publicationRequestType')]);
                if (!$publicationRequestType) {
                    throw new HttpException(400, "An invalid publication request type was passed");
                }
                $mmRequest->setPublicationRequestType($publicationRequestType);
                $mmRequest->setIntendedUse($request->request->get('intendedUse'));
                $mmRequest->setCompletionDate(\DateTime::createFromFormat('Y-m-d', $request->request->get('completionDate')));
                $mmRequest->setDescription($request->request->get('description'));
                $mmRequest->setIsPhotographyRequired($request->request->get('isPhotographyRequired'));
                break;
            default:
                throw $this->createNotFoundException('You passed an invalid request type.');
        }

        // set common fields
        $mmRequest->setFirstName($request->request->get('firstName'));
        $mmRequest->setLastName($request->request->get('lastName'));
        $mmRequest->setEmail($request->request->get('email'));
        $mmRequest->setPhone($request->request->get('phone'));
        $mmRequest->setDepartment($request->request->get('department'));

        // Update request status
        if ($request->request->get('status')) {
            $status = $this->getDoctrine()->getRepository(MultimediaRequestStatus::class)->find($request->request->get('status')['id']);
            if ($status) {
                $mmRequest->setStatus($status);
            }
        }

        // Update request assignee
        if ($request->request->get('assignee')) {
            $assignee = $this->getDoctrine()->getRepository(MultimediaRequestAssignee::class)->find($request->request->get('assignee')['id']);
            // Only try to set the assignee if he/she is found.
            if ($assignee) {
                $mmRequest->setAssignee($assignee);
            }
        } else {
            $mmRequest->setAssignee(null);
        }

        // Status notes
        foreach ($request->request->get('statusNotes') as $statusNote) {
            // a new status note won't have an ID
            if (!isset($statusNote['id'])) {
                $note = new MultimediaRequestStatusNote();
                $note->setMultimediaRequest($mmRequest);
                $note->setNote($statusNote['note']);

                $noteErrors = $this->service->validate($note);
                if (count($noteErrors) > 0) {
                    $serialized = $serializer->serialize($noteErrors, 'json');
                    $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
                    return $response;
                }
                $em->persist($note); // persist but don't save until the end
            }

        }
        // Compare and delete any status notes not in the updated list
        $this->service->statusNoteCollectionCompare($mmRequest->getStatusNotes(), $request->request->get('statusNotes'));

        // validate request
        $errors = $this->service->validate($mmRequest);
        if (count($errors) > 0) {
            $serialized = $serializer->serialize($errors, 'json');
            $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
            return $response;
        }

        // save the request
        $em->persist($mmRequest);
        $em->flush();

        $serialized = $serializer->serialize($mmRequest, 'json');
        $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
        return $response;
    }

    /**
     * Update a headshot time slot to the database
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function putMultimediarequestHeadshotdateAction(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->container->get('jms_serializer');

        $headshotDate = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->find($request->request->get('id'));


        $headshotDate->setStartTime(\DateTime::createFromFormat('g:i a', $request->request->get('startTime')));
        $headshotDate->setEndTime(\DateTime::createFromFormat('g:i a', $request->request->get('endTime')));

        // validate request
        $errors = $this->service->validate($headshotDate);
        if (count($errors) > 0) {
            $serialized = $serializer->serialize($errors, 'json');
            $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
            return $response;
        }

        // save the assignee
        $em->persist($headshotDate);
        $em->flush();

        $serialized = $serializer->serialize($headshotDate, 'json');
        $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
        return $response;
    }

    /**
     * Delete a multimedia request from the database
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_DELETE')")
     */
    public function deleteMultimediarequestAction($id): Response
    {
        $mmRequest = $this->getDoctrine()->getRepository(MultimediaRequest::class)->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($mmRequest);
        $em->flush();

        $response = new Response('Multimedia request has been deleted.', 204, array('Content-Type' => 'application/json'));
        return $response;
    }

    /**
     * Delete a headshot time slot from the database
     *
     * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MULTIMEDIA_ADMIN')")
     */
    public function deleteMultimediarequestHeadshotdateAction($id): Response
    {
        $timeSlot = $this->getDoctrine()->getRepository(PhotoHeadshotDate::class)->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($timeSlot);
        $em->flush();

        $response = new Response("Headshot time slot {$id} has been deleted.", 204, array('Content-Type' => 'application/json'));
        return $response;
    }
}
