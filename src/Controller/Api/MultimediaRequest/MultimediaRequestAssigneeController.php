<?php
// NO LONGER USED AS OF 2025 (hadn't been since 2018) - kept for reference

namespace App\Controller\Api\MultimediaRequest;

//use Doctrine\ORM\EntityManagerInterface;
//use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
//use Symfony\Component\ExpressionLanguage\Expression;
//use Symfony\Component\Routing\Annotation\Route;
//use FOS\RestBundle\Controller\Annotations as Rest;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Security\Http\Attribute\IsGranted;
//use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
//use App\Entity\MultimediaRequest\MultimediaRequestAssigneeStatus;
//use App\Service\MultimediaRequestService;
//use Symfony\Component\Serializer\SerializerInterface;
//
class MultimediaRequestAssigneeController extends AbstractFOSRestController
{
//	private MultimediaRequestService $service;
//	private ManagerRegistry $doctrine;
//	private SerializerInterface $serializer;
//	private EntityManagerInterface $em;
//
//	public function __construct(MultimediaRequestService $service, ManagerRegistry $doctrine, SerializerInterface $serializer, EntityManagerInterface $em)
//	{
//		$this->service = $service;
//		$this->doctrine = $doctrine;
//		$this->serializer = $serializer;
//		$this->em = $em;
//	}
//
//	/**
//	 * Get all multimedia request assignees
//	 */
//	#[Rest\Get(path: "/")]
//	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MULTIMEDIA_VIEW")'))]
//	public function getMultimediaassigneesAction(): Response
//	{
//		$assignees = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->findBy([], ['lastName' => 'asc']);
//
//		$serialized = $this->serializer->serialize($assignees, 'json', ['groups' => 'multi']);
//		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
//	}
//
//	/**
//	 * Get all multimedia request assignees by type
//	 */
//	#[Rest\Get(path: "/type/{type}", defaults: ['type' => null])]
//	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MULTIMEDIA_VIEW")'))]
//	public function getMultimediaassigneesByTypeAction($type): Response
//	{
//		$filteredAssignees = [];
//		$assignees = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->findBy([], ['lastName' => 'asc']);
//
//		// If we are getting a certain type of assignee, get a list of only those assignees who have the request type attributed to them
//		if ($assignees) {
//			foreach ($assignees as $assignee) {
//				if ($assignee->hasAssignableRequestType($type)) {
//					$filteredAssignees[] = $assignee;
//				}
//			}
//		}
//
//		$serialized = $this->serializer->serialize($filteredAssignees, 'json', ['groups' => 'multi']);
//		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
//	}
//
//	/**
//	 * Get all status options for a multimedia request assignee
//	 */
//	#[Rest\Get(path: "/statuses")]
//	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MULTIMEDIA_ADMIN")'))]
//	public function getMultimediaassigneestatusesAction(): Response
//	{
//		$statuses = $this->doctrine->getRepository(MultimediaRequestAssigneeStatus::class)->findBy([], ['statusSlug' => 'asc']);
//
//		$serialized = $this->serializer->serialize($statuses, 'json');
//		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
//	}
//
//	/**
//	 * Get a single request assignee
//	 */
//	#[Rest\Get(path: "/{id}")]
//	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MULTIMEDIA_VIEW")'))]
//	public function getMultimediaassigneeAction($id): Response
//	{
//		$assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->findOneBy(['id' => $id]);
//		if (!$assignee) {
//			return new Response("The multimedia request assignee was not found.", 404, ['Content-Type' => 'application/json']);
//		}
//
//		$serialized = $this->serializer->serialize($assignee, 'json', ['groups' => 'multi']);
//		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
//	}
//
//	/**
//	 * Save a multimedia request assignee to the database
//	 */
//	#[Rest\Post()]
//	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MULTIMEDIA_ADMIN")'))]
//	public function postMultimediaassigneeAction(Request $request): Response
//	{
//		$assignee = new MultimediaRequestAssignee();
//
//		// set fields
//		$assignee->setFirstName($request->request->get('firstName'));
//		$assignee->setLastName($request->request->get('lastName'));
//		$assignee->setEmail($request->request->get('email'));
//		$assignee->setPhone($request->request->get('phone'));
//		$assignee->setAssignableForRequestType($request->get('assignableRequestTypes'));
//		// status
//		$status = $this->doctrine->getRepository(MultimediaRequestAssigneeStatus::class)->find($request->get('status')['id']);
//		if ($status) {
//			$assignee->setStatus($status);
//		}
//
//		// validate assignee
//		$errors = $this->service->validate($assignee);
//		if (count($errors) > 0) {
//			$serialized = $this->serializer->serialize($errors, 'json');
//			return new Response($serialized, 422, ['Content-Type' => 'application/json']);
//		}
//
//		// persist the assignee and commit
//		$this->em->persist($assignee);
//		$this->em->flush();
//
//		$serialized = $this->serializer->serialize($assignee, 'json', ['groups' => 'multi']);
//		return new Response($serialized, 201, ['Content-Type' => 'application/json']);
//	}
//
//	/**
//	 * Update a multimedia request assignee to the database
//	 */
//	#[Rest\Put()]
//	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MULTIMEDIA_ADMIN")'))]
//	public function putMultimediaassigneeAction(Request $request): Response
//	{
//		$assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($request->request->get('id'));
//
//		// set fields
//		$assignee->setFirstName($request->request->get('firstName'));
//		$assignee->setLastName($request->request->get('lastName'));
//		$assignee->setEmail($request->request->get('email'));
//		$assignee->setPhone($request->request->get('phone'));
//		$assignee->setAssignableForRequestType($request->get('assignableRequestTypes'));
//		// status
//		$status = $this->doctrine->getRepository(MultimediaRequestAssigneeStatus::class)->find($request->get('status')['id']);
//		if ($status) {
//			$assignee->setStatus($status);
//		}
//
//		// validate assignee
//		$errors = $this->service->validate($assignee);
//		if (count($errors) > 0) {
//			$serialized = $this->serializer->serialize($errors, 'json');
//			return new Response($serialized, 422, ['Content-Type' => 'application/json']);
//		}
//
//		// save the assignee
//		$this->em->persist($assignee);
//		$this->em->flush();
//
//		$serialized = $this->serializer->serialize($assignee, 'json', ['groups' => 'multi']);
//		return new Response($serialized, 201, ['Content-Type' => 'application/json']);
//	}
//
//	/**
//	 * Delete a multimedia request assignee from the database
//	 */
//	#[Rest\Delete(path: "{id}")]
//	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MULTIMEDIA_ADMIN")'))]
//	public function deleteMultimediaassigneeAction($id): Response
//	{
//		$assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($id);
//
//		if (!$assignee) {
//			return new Response("The multimedia request assignee was not found.", 404, ['Content-Type' => 'application/json']);
//		}
//
//		$this->em->remove($assignee);
//		$this->em->flush();
//
//		return new Response('Assignee has been deleted.', 204, ['Content-Type' => 'application/json']);
//	}
}
