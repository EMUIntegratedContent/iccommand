<?php

namespace App\Controller\Api\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Service\UserService;

class UserController extends AbstractController
{

	private UserService $service;
	private SerializerInterface $serializer;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;

	public function __construct(UserService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em)
	{
		$this->service = $service;
		$this->serializer = $serializer;
		$this->doctrine = $doctrine;
		$this->em = $em;
	}

	/**
	 * Get all users
	 */
	#[Route('/users', methods: ['GET'])]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	public function getUsersAction(): Response
	{
		$users = $this->doctrine->getRepository(User::class)->findBy([], ['enabled' => 'DESC', 'username' => 'ASC']);
		$serialized = $this->serializer->serialize($users, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Return an individual user (by username)
	 */
	#[Route('/users/{username}', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function getUserAction($username): Response
	{
		$user = $this->doctrine->getRepository(User::class)->findOneByUsername($username);

		$serialized = $this->serializer->serialize($user, 'json');
		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Return all defined roles
	 */
	#[Route('/roles', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN")'))]
	public function getRolesAction(): Response
	{
		$roles = $this->getParameter('security.role_hierarchy.roles');

		$serialized = $this->serializer->serialize($roles, 'json');
		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Update a user's information and roles
	 */
	#[Route('/users/{username}', methods: ['PUT'])]
	#[IsGranted('ROLE_USER')]
	public function putUserAction(Request $request, string $username): Response
	{
		$user = $this->doctrine->getRepository(User::class)->findOneByUsername($username);

		if (!$user) {
			throw $this->createNotFoundException('The user ' . $username . ' was not found.');
		}

		// Decode JSON body directly — $request->get() was removed in Symfony 8,
		// and $request->request->get() rejects non-scalar values like the roles array.
		$data = json_decode($request->getContent(), true);

		$user->setFirstName($data['firstName']);
		$user->setLastName($data['lastName']);
		$user->setJobTitle($data['jobTitle']);
		$user->setDepartment($data['department']);
		$user->setPhone($data['phone']);
		$user->setRoles($data['roles']);
		$user->setEnabled($data['enabled']);
		$this->em->persist($user);
		$this->em->flush();

		return new Response('User ' . $username . ' was updated successfully.', 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Return all users of an application
	 */
	#[Route('/appusers/{rolePrefix}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN")'))]
	public function getAppusersAction(string $rolePrefix): Response
	{
		$mapAppUsers = $this->doctrine->getRepository(User::class)->findByLikeRole($rolePrefix);

		$serialized = $this->serializer->serialize($mapAppUsers, 'json');
		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Return all users that are NOT part of an application
	 */
	#[Route('/appusers/not/{rolePrefix}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN")'))]
	public function getAppusersNotAction(string $rolePrefix): Response
	{
		$mapAppUsers = $this->doctrine->getRepository(User::class)->findByLikeRole($rolePrefix, true);

		$serialized = $this->serializer->serialize($mapAppUsers, 'json');
		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}
}
