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
use App\Security\RoleAssignmentPolicy;
use App\Service\UserService;

class UserController extends AbstractController
{

	private UserService $service;
	private SerializerInterface $serializer;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;
	private RoleAssignmentPolicy $policy;

	/** Profile fields a user may edit on their own account. */
	private const PROFILE_FIELDS = [
		'firstName' => ['getFirstName', 'setFirstName'],
		'lastName' => ['getLastName', 'setLastName'],
		'jobTitle' => ['getJobTitle', 'setJobTitle'],
		'department' => ['getDepartment', 'setDepartment'],
		'phone' => ['getPhone', 'setPhone'],
	];

	public function __construct(UserService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em, RoleAssignmentPolicy $policy)
	{
		$this->service = $service;
		$this->serializer = $serializer;
		$this->doctrine = $doctrine;
		$this->em = $em;
		$this->policy = $policy;
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
		// Users may read their own record; global admins may read anyone's.
		if (!$this->isSelf($username) && !$this->policy->isGlobalAdmin()) {
			throw $this->createAccessDeniedException('You may only view your own user record.');
		}

		$user = $this->doctrine->getRepository(User::class)->findOneByUsername($username);
		if (!$user) {
			throw $this->createNotFoundException('The user ' . $username . ' was not found.');
		}

		$serialized = $this->serializer->serialize($user, 'json');
		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Return all defined roles
	 */
	#[Route('/roles', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN") or is_granted("ROLE_REDIRECT_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_CRIMELOG_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_EMERGENCY_ADMIN") or is_granted("ROLE_CAS_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN")'))]
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
		if (!is_array($data)) {
			return new Response('The request body must be a JSON object.', 400, array('Content-Type' => 'application/json'));
		}

		$isSelf = $this->isSelf($username);
		$isGlobalAdmin = $this->policy->isGlobalAdmin();

		// Only the user themself, a global admin, or a module admin (managing
		// their module's roles) may update another user's record.
		if (!$isSelf && !$isGlobalAdmin && !$this->policy->isAnyAdmin()) {
			throw $this->createAccessDeniedException('You may only update your own profile.');
		}

		// Profile fields: only the user themself or a global admin may change them.
		// Module admins send the unchanged values back from the Manage page.
		foreach (self::PROFILE_FIELDS as $field => [$getter, $setter]) {
			if (!array_key_exists($field, $data)) {
				continue;
			}
			$value = $data[$field] === null ? null : (string) $data[$field];
			if ($value !== $user->$getter()) {
				if (!$isSelf && !$isGlobalAdmin) {
					throw $this->createAccessDeniedException('You may not change another user\'s profile.');
				}
				$user->$setter($value);
			}
		}

		// Roles: every added or removed role must be one the caller may assign.
		if (array_key_exists('roles', $data)) {
			if (!is_array($data['roles'])) {
				return new Response('"roles" must be an array.', 422, array('Content-Type' => 'application/json'));
			}
			$diff = RoleAssignmentPolicy::diff($user->getRoles(), $data['roles']);
			foreach (array_merge($diff['added'], $diff['removed']) as $role) {
				if (!$this->policy->isKnownRole($role)) {
					return new Response(json_encode('Unknown role: ' . $role), 422, array('Content-Type' => 'application/json'));
				}
				if (!$this->policy->canAssign($role)) {
					throw $this->createAccessDeniedException('You may not grant or revoke ' . $role . '.');
				}
			}
			if ($diff['added'] || $diff['removed']) {
				$roles = array_values(array_diff($user->getRoles(), ['ROLE_USER'], $diff['removed']));
				$user->setRoles(array_values(array_unique(array_merge($roles, $diff['added']))));
			}
		}

		// Enabled flag: only global admins may enable or disable an account.
		if (array_key_exists('enabled', $data)) {
			$enabled = filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
			if ($enabled !== (int) $user->getEnabled()) {
				if (!$isGlobalAdmin) {
					throw $this->createAccessDeniedException('Only global admins may enable or disable accounts.');
				}
				$user->setEnabled($enabled);
			}
		}

		$this->em->persist($user);
		$this->em->flush();

		return new Response('User ' . $username . ' was updated successfully.', 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Return all users of an application
	 */
	#[Route('/appusers/{rolePrefix}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN") or is_granted("ROLE_REDIRECT_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_CRIMELOG_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_EMERGENCY_ADMIN") or is_granted("ROLE_CAS_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN")'))]
	public function getAppusersAction(string $rolePrefix): Response
	{
		$this->denyUnlessManagesPrefix($rolePrefix);

		$mapAppUsers = $this->doctrine->getRepository(User::class)->findByLikeRole($rolePrefix);

		$serialized = $this->serializer->serialize($mapAppUsers, 'json');
		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Return all users that are NOT part of an application
	 */
	#[Route('/appusers/not/{rolePrefix}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN") or is_granted("ROLE_REDIRECT_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_CRIMELOG_ADMIN") or is_granted("ROLE_DEPARTMENTS_ADMIN") or is_granted("ROLE_PHOTO_ADMIN") or is_granted("ROLE_EMERGENCY_ADMIN") or is_granted("ROLE_CAS_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN")'))]
	public function getAppusersNotAction(string $rolePrefix): Response
	{
		$this->denyUnlessManagesPrefix($rolePrefix);

		$mapAppUsers = $this->doctrine->getRepository(User::class)->findByLikeRole($rolePrefix, true);

		$serialized = $this->serializer->serialize($mapAppUsers, 'json');
		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * Only admins of the module identified by the prefix (e.g. "ROLE_MAP_"),
	 * or global admins, may list that module's users.
	 */
	private function denyUnlessManagesPrefix(string $rolePrefix): void
	{
		if (!RoleAssignmentPolicy::isValidPrefix($rolePrefix)) {
			throw $this->createNotFoundException('Unknown application.');
		}
		if (!$this->policy->canManagePrefix($rolePrefix)) {
			throw $this->createAccessDeniedException('You do not administer this application.');
		}
	}

	private function isSelf(string $username): bool
	{
		$current = $this->getUser();

		return $current !== null && $current->getUserIdentifier() === $username;
	}
}
