<?php
namespace App\Service;

use App\Entity\PhotoRequest\PhotoRequest;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The photo service is used primarily for CRUD actions on Photo Requests
 */
class PhotoRequestService {
  private AuthorizationCheckerInterface $authorizationChecker;
  private ValidatorInterface $validator;
  private ObjectManager $em;

  /**
   * The constructor of the service of the redirects.
   */
  public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine) {
    $this->authorizationChecker = $authorizationChecker;
    $this->validator = $validator;
    $this->em = $doctrine->getManager();
  }

	/**
	 * Uses the Symfony container's validator to validate fields for a program.
	 * @param PhotoRequest $program
	 * @return ConstraintViolationList A list of errors.
	 */
  public function validate($photoReq): ConstraintViolationList {
    return $this->validator->validate($photoReq);
  }

	/**
	 * Fetches the permissions of the user for managing photo requests.
	 * @return array The user's permissions for managing photo requests.
	 */
	#[ArrayShape(['user' => "bool", 'admin' => "bool"])]
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
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	#[ArrayShape(['programs' => "array", 'totalRows' => "integer"])]
	public function getPhotoRequestsPagination($currentPage, $pageSize)
	{
		$repository = $this->em->getRepository(PhotoRequest::class);
		return $repository->paginatedRequests($currentPage, $pageSize);
	}
}
