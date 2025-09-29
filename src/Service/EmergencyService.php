<?php

namespace App\Service;

use App\Entity\Emergency\EmergencyBanner;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The emergency service is used primarily for CRUD actions on Emergency Banners and Notices
 */
class EmergencyService
{
	private AuthorizationCheckerInterface $authorizationChecker;
	private ValidatorInterface $validator;
	private ObjectManager $em;

	/**
	 * The constructor of the service of the emergency banners and notices.
	 */
	public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine)
	{
		$this->authorizationChecker = $authorizationChecker;
		$this->validator = $validator;
		$this->em = $doctrine->getManager();
	}

	/**
	 * Uses the Symfony container's validator to validate fields for a emergency banner.
	 * @param EmergencyBanner $emergencyBanner
	 * @return ConstraintViolationList A list of errors.
	 */
	public function validateBanner($emergencyBanner): ConstraintViolationList
	{
		return $this->validator->validate($emergencyBanner);
	}

	/**
	 * Uses the Symfony container's validator to validate fields for a emergency notice.
	 * @param EmergencyNotice $emergencyNotice
	 * @return ConstraintViolationList A list of errors.
	 */
	public function validateNotice($emergencyNotice): ConstraintViolationList
	{
		return $this->validator->validate($emergencyNotice);
	}

	/**
	 * Fetches the permissions of the user for managing emergency banners and notices.
	 * @return array The user's permissions for managing emergency banners and notices.
	 */
	public function getEmergencyAppPermissions(): array
	{
		// Set all permissions to false as default.
		$emPermissions = array(
			'admin' => false,
			'edit' => false,
			'delete' => false,
			'view' => false,
		);

		// The admins automatically have all the permissions.
		if ($this->authorizationChecker->isGranted('ROLE_EMERGENCY_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
			$emPermissions['admin'] = true;
			$emPermissions['edit'] = true;
			$emPermissions['delete'] = true;
			$emPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_EMERGENCY_DELETE')) {
			$emPermissions['edit'] = true;
			$emPermissions['delete'] = true;
			$emPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_EMERGENCY_EDIT')) {
			$emPermissions['edit'] = true;
			$emPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_EMERGENCY_VIEW')) {
			$emPermissions['view'] = true;
		}

		return $emPermissions;
	}

	/**
	 * Gets the emergency notices for the forced emergency page.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getNotices()
	{
		// $repository = $this->em->getRepository(PhotoRequest::class);
		// return $repository->paginatedPhotoRequests($currentPage, $pageSize, $statuses, $category);
	}

	/**
	 * Gets the emergency banner.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getBanner($searchTerm)
	{
		// Get the Doctrine repository
		$repository = $this->em->getRepository(EmergencyBanner::class);
		return $repository->getBanner();
	}
}
