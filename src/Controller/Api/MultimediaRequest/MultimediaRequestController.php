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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MultimediaRequestService;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class MultimediaRequestController extends AbstractFOSRestController{
	private SerializerInterface $serializer;
	private MultimediaRequestService $service;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;

	public function __construct(MultimediaRequestService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em){
		$this->service = $service;
		$this->serializer = $serializer;
		$this->doctrine = $doctrine;
		$this->em = $em;
	}

	/**
	 * EXTERNAL API ENDPOINT. Get headshot time slots.
	 */
	#[Rest\Get(path: "external/headshotdates")]
	public function getExternalMultimediarequestHeadshotdatesAction(): Response{
		// Get all future headshot time slots (query in repository).
		$futureTimeSlots = $this->doctrine->getRepository(PhotoHeadshotDate::class)->findByFutureDateTime();
		if(!$futureTimeSlots){
			throw $this->createNotFoundException('No future time slots were found.');
		}

		$serialized = $this->serializer->serialize($futureTimeSlots, 'json');
		return new Response($serialized, 201, ['Content-Type' => 'application/json']);
	}

	/**
	 * EXTERNAL API ENDPOINT. Get publication types.
	 */
	#[Rest\Get(path: "external/publicationtypes")]
	public function getExternalMultimediarequestPublicationtypesAction(): Response{
		$types = $this->doctrine->getRepository(PublicationRequestType::class)->findBy([], ['slug' => 'asc']);

		$serialized = $this->serializer->serialize($types, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * EXTERNAL API ENDPOINT. Handle new multimedia request.
	 */
	#[Rest\Post(path: "external/multimediarequests")]
	public function postExternalMultimediarequestAction(Request $request): Response{
		// Determine which type of Multimedia Request to create based on type & set type-specific fields
		switch($request->request->get('requestType')){
			case 'headshot':
				$mmRequest = new HeadshotRequest();

				// Get the time slot
				$timeSlot = $this->doctrine->getRepository(PhotoHeadshotDate::class)->find($request->get('headshotTimeSlot'));
				if($timeSlot){
					$mmRequest->setTimeSlot($timeSlot);
				}
				$mmRequest->setDescription($request->request->get('description'));
				break;
			case 'photo':
				$mmRequest = new PhotoRequest();

				// Get the photo request type
				$photoRequestType = $this->doctrine->getRepository(PhotoRequestType::class)->find($request->get('photoRequestType')['id']);
				if(!$photoRequestType){
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
				$publicationRequestType = $this->doctrine->getRepository(PublicationRequestType::class)->find($request->request->get('publicationRequestType'));
				if(!$publicationRequestType){
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
		$status = $this->doctrine->getRepository(MultimediaRequestStatus::class)->findOneBy(['statusSlug' => 'new']);
		if($status){
			$mmRequest->setStatus($status);
		}

		// validate request
		$errors = $this->service->validate($mmRequest);
		if(count($errors) > 0){
			$serialized = $this->serializer->serialize($errors, 'json');
			return new Response($serialized, 422, ['Content-Type' => 'application/json']);
		}

		// persist the assignee and commit
		$this->em->persist($mmRequest);
		$this->em->flush();

		$serialized = $this->serializer->serialize($mmRequest, 'json', ['groups' => 'multi', DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']);
		return new Response($serialized, 201, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get all multimedia requests (by optional type)
	 */
	#[Rest\Get(path: "/list/{type?}")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_VIEW')]
	public function getMultimediarequestsListAction(?string $type = null): Response{
		$mmRequests = match ($type) {
			'headshot' => $this->doctrine->getRepository(HeadshotRequest::class)->findBy([], ['created' => 'asc']),
			'photo' => $this->doctrine->getRepository(PhotoRequest::class)->findBy([], ['created' => 'asc']),
			'video' => $this->doctrine->getRepository(VideoRequest::class)->findBy([], ['created' => 'asc']),
			'publication' => $this->doctrine->getRepository(PublicationRequest::class)->findBy([], ['created' => 'asc']),
			default => $this->doctrine->getRepository(MultimediaRequest::class)->findBy([], ['created' => 'asc']),
		};

		$serialized = $this->serializer->serialize($mmRequests, 'json', ['groups' => 'multi', DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']);
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get a single request
	 */
	#[Rest\Get(path: "/request/{id}")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_VIEW')]
	public function getMultimediarequestAction($id): Response{
		$mmRequest = $this->doctrine->getRepository(MultimediaRequest::class)->findOneBy(['id' => $id]);
		if(!$mmRequest){
			return new Response("The multimedia request was not found.", 404, ['Content-Type' => 'application/json']);
		}

		$serialized = $this->serializer->serialize($mmRequest, 'json', ['groups' => 'multi', DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']);
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get all photo request types
	 */
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_VIEW')]
	public function getPhotorequestTypesAction(): Response{
		$types = $this->doctrine->getRepository(PhotoRequestType::class)->findBy([], ['slug' => 'asc']);

		$serialized = $this->serializer->serialize($types, 'json', ['groups' => 'multi']);
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get all publication request types
	 */
	#[Rest\Get(path: "/pubtypes")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_VIEW')]
	public function getPublicationrequestTypesAction(): Response{
		$types = $this->doctrine->getRepository(PublicationRequestType::class)->findBy([], ['slug' => 'asc']);

		$serialized = $this->serializer->serialize($types, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get all status options for a multimedia request
	 */
	#[Rest\Get(path: "/statuses")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_VIEW')]
	public function getMultimediarequestStatusesAction(): Response{
		$statuses = $this->doctrine->getRepository(MultimediaRequestStatus::class)->findBy([], ['statusSlug' => 'asc']);

		$serialized = $this->serializer->serialize($statuses, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get headshot time slots for a specific date
	 */
	#[Rest\Get(path: "/headshotdates/{year}/{month}/{day}")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_VIEW')]
	public function getMultimediarequestHeadshotdatesAction($year, $month, $day): Response{
		$dateOfShoot = Carbon::parse("$year-$month-$day");
		$timeSlots = $this->doctrine->getRepository(PhotoHeadshotDate::class)->findBy(['dateOfShoot' => $dateOfShoot], ['startTime' => 'asc']);

		$serialized = $this->serializer->serialize($timeSlots, 'json', [DateTimeNormalizer::FORMAT_KEY => 'g:i a']);
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get headshot time slots
	 */
	#[Rest\Get(path: "/headshotdates/slots/{withPast?}")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_VIEW')]
	public function getMultimediarequestHeadshottimeslotsAction(bool $withPast = false): Response{
		// Get all future headshot time slots (query in repository).
		$timeSlots = $withPast ?
			$this->doctrine->getRepository(PhotoHeadshotDate::class)->findBy([], ['dateOfShoot' => 'ASC']) :
			$this->doctrine->getRepository(PhotoHeadshotDate::class)->findByFutureDateTime();

		if(!$timeSlots){
			throw $this->createNotFoundException('No time slots were found.');
		}

		$serialized = $this->serializer->serialize($timeSlots, 'json', ['groups' => 'multi', DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']);
		return new Response($serialized, 201, ['Content-Type' => 'application/json']);
	}

	/**
	 * Add a headshot time slot to the database
	 */
	#[Rest\Post(path: "/headshotdates")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_ADMIN')]
	public function postMultimediarequestHeadshotdateAction(Request $request): Response{
		$headshotDate = new PhotoHeadshotDate();

		$headshotDate->setDateOfShoot(\DateTime::createFromFormat('Y-m-d', $request->request->get('dateOfShoot')));
		$headshotDate->setStartTime(\DateTime::createFromFormat('g:i a', $request->request->get('startTime')));
		$headshotDate->setEndTime(\DateTime::createFromFormat('g:i a', $request->request->get('endTime')));

		// validate request
		$errors = $this->service->validate($headshotDate);
		if(count($errors) > 0){
			$serialized = $this->serializer->serialize($errors, 'json');
			return new Response($serialized, 422, ['Content-Type' => 'application/json']);
		}

		// save the assignee
		$this->em->persist($headshotDate);
		$this->em->flush();

		$serialized = $this->serializer->serialize($headshotDate, 'json', [DateTimeNormalizer::FORMAT_KEY => 'g:i a']);
		return new Response($serialized, 201, ['Content-Type' => 'application/json']);
	}

	/**
	 * Update a multimedia request to the database
	 */
	#[Rest\Put()]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_EDIT')]
	public function putMultimediarequestAction(Request $request): Response{
		$mmRequest = $this->doctrine->getRepository(MultimediaRequest::class)->find($request->request->get('id'));

		// Determine which type of Multimedia Request to create based on type & set type-specific fields
		switch($request->request->get('requestType')){
			case 'headshot':
				// Get the time slot
				$timeSlot = $this->doctrine->getRepository(PhotoHeadshotDate::class)->find($request->get('timeSlot')['id']);
				if($timeSlot){
					$mmRequest->setTimeSlot($timeSlot);
				}
				$mmRequest->setDescription($request->request->get('description'));
				break;
			case 'photo':
				// Get the photo request type
				$photoRequestType = $this->doctrine->getRepository(PhotoRequestType::class)->find($request->get('photoRequestType')['id']);
				if(!$photoRequestType){
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
				$mmRequest->setCompletionDate(\DateTime::createFromFormat('Y-m-d', $request->request->get('completionDate')));
				$mmRequest->setDescription($request->request->get('description'));
				break;
			case 'publication':
				// Get the publication request type
				$publicationRequestType = $this->doctrine->getRepository(PublicationRequestType::class)->findOneBy(['slug' => $request->get('publicationRequestType')]);
				if(!$publicationRequestType){
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
		if($request->get('status')){
			$status = $this->doctrine->getRepository(MultimediaRequestStatus::class)->find($request->get('status')['id']);
			if($status){
				$mmRequest->setStatus($status);
			}
		}

		// Update request assignee
		if($request->get('assignee')){
			$assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($request->get('assignee')['id']);
			// Only try to set the assignee if he/she is found.
			if($assignee){
				$mmRequest->setAssignee($assignee);
			}
		}
		else{
			$mmRequest->setAssignee(null);
		}

		// Status notes
		foreach($request->get('statusNotes') as $statusNote){
			// a new status note won't have an ID
			if(!isset($statusNote['id'])){
				$note = new MultimediaRequestStatusNote();
				$note->setMultimediaRequest($mmRequest);
				$note->setNote($statusNote['note']);

				$noteErrors = $this->service->validate($note);
				if(count($noteErrors) > 0){
					$serialized = $this->serializer->serialize($noteErrors, 'json');
					return new Response($serialized, 422, array('Content-Type' => 'application/json'));
				}
				$this->em->persist($note); // persist but don't save until the end
			}

		}
		// Compare and delete any status notes not in the updated list
		$this->service->statusNoteCollectionCompare($mmRequest->getStatusNotes(), $request->get('statusNotes'));

		// validate request
		$errors = $this->service->validate($mmRequest);
		if(count($errors) > 0){
			$serialized = $this->serializer->serialize($errors, 'json');
			return new Response($serialized, 422, array('Content-Type' => 'application/json'));
		}

		// save the request
		$this->em->persist($mmRequest);
		$this->em->flush();

		$serialized = $this->serializer->serialize($mmRequest, 'json', ['groups' => 'multi', DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']);
		return new Response($serialized, 201, array('Content-Type' => 'application/json'));
	}

	/**
	 * Update a headshot time slot to the database
	 */
	#[Rest\Put(path: "/headshotdates")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_ADMIN')]
	public function putMultimediarequestHeadshotdateAction(Request $request): Response{
		$headshotDate = $this->doctrine->getRepository(PhotoHeadshotDate::class)->find($request->request->get('id'));


		$headshotDate->setStartTime(\DateTime::createFromFormat('g:i a', $request->request->get('startTime')));
		$headshotDate->setEndTime(\DateTime::createFromFormat('g:i a', $request->request->get('endTime')));

		// validate request
		$errors = $this->service->validate($headshotDate);
		if(count($errors) > 0){
			$serialized = $this->serializer->serialize($errors, 'json');
			return new Response($serialized, 422, array('Content-Type' => 'application/json'));
		}

		// save the assignee
		$this->em->persist($headshotDate);
		$this->em->flush();

		$serialized = $this->serializer->serialize($headshotDate, 'json', [DateTimeNormalizer::FORMAT_KEY => 'g:i a']);
		return new Response($serialized, 201, array('Content-Type' => 'application/json'));
	}

	/**
	 * Delete a multimedia request from the database
	 */
	#[Rest\Delete(path: "/request/{id}")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_DELETE')]
	public function deleteMultimediarequestAction($id): Response{
		$mmRequest = $this->doctrine->getRepository(MultimediaRequest::class)->find($id);

		$this->em->remove($mmRequest);
		$this->em->flush();

		return new Response('Multimedia request has been deleted.', 204, array('Content-Type' => 'application/json'));
	}

	/**
	 * Delete a headshot time slot from the database
	 */
	#[Rest\Delete(path: "/headshotdates/{id}")]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	#[IsGranted('ROLE_MULTIMEDIA_ADMIN')]
	public function deleteMultimediarequestHeadshotdateAction($id): Response{
		$timeSlot = $this->doctrine->getRepository(PhotoHeadshotDate::class)->find($id);

		$this->em->remove($timeSlot);
		$this->em->flush();

		return new Response("Headshot time slot $id has been deleted.", 204, array('Content-Type' => 'application/json'));
	}
}
