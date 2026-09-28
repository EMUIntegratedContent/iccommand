<?php

namespace App\Service;

use App\Entity\PhotoRequest\PhotoRequest;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The photo service is used primarily for CRUD actions on Photo Requests
 */
class PhotoRequestService
{
	private AuthorizationCheckerInterface $authorizationChecker;
	private ValidatorInterface $validator;
	private ObjectManager $em;

	/**
	 * The constructor of the service of the photo requests.
	 */
	public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine)
	{
		$this->authorizationChecker = $authorizationChecker;
		$this->validator = $validator;
		$this->em = $doctrine->getManager();
	}

	/**
	 * Builds a new photo request from submitted form data. Used by the internal form and the
	 * emich.edu submission route. Only the public form fields are read; status, assignment and
	 * completion are never taken from the request.
	 */
	public function createFromData(array $data): PhotoRequest
	{
		// Non-scalar values (arrays from a malformed body) are treated as empty.
		$str = fn (string $key, string $default = '') => isset($data[$key]) && is_scalar($data[$key]) ? (string) $data[$key] : $default;
		$date = fn (string $key) => $str($key) !== '' ? new \DateTime($str($key)) : null;

		$photoRequest = new PhotoRequest();
		$photoRequest->setShootType($str('shootType', 'photoshoot'));
		$photoRequest->setFirstName($str('firstName'));
		$photoRequest->setLastName($str('lastName'));
		$photoRequest->setEmail($str('email'));
		$photoRequest->setPhone($str('phone'));
		$photoRequest->setDepartment($str('department'));
		$photoRequest->setShootName($str('shootName'));
		$photoRequest->setPhotoType($str('photoType'));
		$photoRequest->setShootDate($date('shootDate'));
		$photoRequest->setStartTime($date('startTime'));
		$photoRequest->setEndTime($date('endTime'));
		$photoRequest->setLocation($str('location'));
		$photoRequest->setDescription($str('description'));
		$photoRequest->setPhotoExplaination($str('photoExplaination'));
		$photoRequest->setIntendedUse($str('intendedUse'));
		$photoRequest->setForUse($str('forUse'));
		$photoRequest->setUrl($str('url'));
		$photoRequest->setDesigner($str('designer'));
		$photoRequest->setCategory($str('category'));
		$photoRequest->setEventDesc($str('eventDesc'));

		return $photoRequest;
	}

	/**
	 * Uses the Symfony container's validator to validate fields for a photo request.
	 * @param PhotoRequest $photoRequest
	 * @return ConstraintViolationList A list of errors.
	 */
	public function validate($photoRequest): ConstraintViolationList
	{
		return $this->validator->validate($photoRequest);
	}

	/**
	 * Fetches the permissions of the user for managing photo requests.
	 * @return array The user's permissions for managing photo requests.
	 */
	public function getPhotoRequestPermissions(): array
	{
		// Set all permissions to false as default.
		$prPermissions = array(
			'admin' => false,
			'create' => false,
			'edit' => false,
			'delete' => false,
			'photog' => false,
			'view' => false,
		);

		// The admins automatically have all the permissions.
		if ($this->authorizationChecker->isGranted('ROLE_PHOTO_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
			$prPermissions['admin'] = true;
			$prPermissions['create'] = true;
			$prPermissions['edit'] = true;
			$prPermissions['delete'] = true;
			$prPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PHOTO_DELETE')) {
			$prPermissions['create'] = true;
			$prPermissions['edit'] = true;
			$prPermissions['delete'] = true;
			$prPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PHOTO_CREATE')) {
			$prPermissions['create'] = true;
			$prPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PHOTO_EDIT')) {
			$prPermissions['edit'] = true;
			$prPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PHOTO_PHOTOG')) {
			$prPermissions['photog'] = true;
			$prPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PHOTO_VIEW')) {
			$prPermissions['view'] = true;
		}

		return $prPermissions;
	}

	/**
	 * Gets the photo requests with pagination.
	 * @param $currentPage
	 * @param $pageSize
	 * @param $statuses
	 * @param $category
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getPhotoRequestsPagination($currentPage, $pageSize, $statuses = null, $category = null)
	{
		$repository = $this->em->getRepository(PhotoRequest::class);
		return $repository->paginatedPhotoRequests($currentPage, $pageSize, $statuses, $category);
	}

	/**
	 * Get categories with counts
	 * @param $statuses
	 * @return array
	 */
	public function getCategoriesWithCounts($statuses = null): array
	{
		$repository = $this->em->getRepository(PhotoRequest::class);
		return $repository->getCategoriesWithCounts($statuses);
	}

	/**
	 * Get photo requests that match the search term.
	 * @param $searchTerm
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getPhotoRequestsByName($searchTerm)
	{
		// Get the Doctrine repository
		$repository = $this->em->getRepository(PhotoRequest::class);
		return $repository->searchResults($searchTerm);
	}

	/**
	 * Return a single photo request
	 * @param $id
	 * @return PhotoRequest
	 */
	public function getPhotoRequest($id)
	{
		// Get the Doctrine repository
		$repository = $this->em->getRepository(PhotoRequest::class);
		return $repository->getPhotoRequest($id);
	}

	/**
	 * Get a PhotoRequest entity by ID
	 * @param $id
	 * @return mixed
	 */
	public function getPhotoRequestEntity($id)
	{
		$repository = $this->em->getRepository(PhotoRequest::class);
		return $repository->getPhotoRequestEntity($id);
	}
}
