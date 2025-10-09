<?php

namespace App\Service;

use App\Entity\Emergency\EmergencyBanner;
use App\Entity\Emergency\EmergencyNotice;
use App\Entity\Emergency\EmergencySeverity;
use App\Repository\Emergency\EmergencyRepository;
use App\Repository\Emergency\EmergencyNoticeRepository;
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
			'edit' => false
		);

		// The admins automatically have all the permissions.
		if ($this->authorizationChecker->isGranted('ROLE_EMERGENCY_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
			$emPermissions['admin'] = true;
			$emPermissions['edit'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_EMERGENCY_EDIT')) {
			$emPermissions['edit'] = true;
		}

		return $emPermissions;
	}

	/**
	 * Gets the emergency notices for the forced emergency page. Possibly not needed.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getNotices(): array
	{
		// TODO: Implement emergency notices retrieval
		return [];
	}

	/**
	 * Gets the emergency banner.
	 * @return EmergencyBanner|null The emergency banner record.
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getBanner(): ?EmergencyBanner
	{
		// Get the Doctrine repository
		/** @var EmergencyRepository $repository */
		$repository = $this->em->getRepository(EmergencyBanner::class);
		return $repository->findOneBannerWithUsername();
	}

	/**
	 * Updates the emergency banner.
	 * @param array $data The banner data to update.
	 * @return array Result with success status, banner, and message.
	 */
	public function updateBanner(array $data): array
	{
		try {
			// Get existing banner or create new one
			$banner = $this->getBanner();

			if (!$banner) {
				// Create new banner if none exists
				$banner = new EmergencyBanner();
			}

			// Update banner properties
			$banner->setDisplayBanner($data['displayBanner'] ?? false);
			$banner->setForceEmergencyPage($data['forceEmergencyPage'] ?? false);

			// Always update banner fields with the provided data, regardless of displayBanner state
			$banner->setSeverity($data['severity'] ? EmergencySeverity::from($data['severity']) : null);
			$banner->setBannerMessage($data['bannerMessage'] ?? null);
			$banner->setBannerTitle($data['bannerTitle'] ?? null);

			// Set updated by user (you'll need to get current user from security context)
			// For now, using a placeholder - you may need to inject Security service
			$banner->setUpdatedBy(1); // TODO: Get actual current user ID

			// Validate the banner
			$errors = $this->validateBanner($banner);
			if (count($errors) > 0) {
				$errorMessages = [];
				foreach ($errors as $error) {
					$errorMessages[] = $error->getMessage();
				}
				return [
					'success' => false,
					'message' => 'Validation failed: ' . implode(', ', $errorMessages)
				];
			}

			// Handle emergency notices
			$this->handleEmergencyNotices($data['notices'] ?? []);

			// Save the banner
			$this->em->persist($banner);
			$this->em->flush();

			// Return updated banner with username
			/** @var EmergencyRepository $repository */
			$repository = $this->em->getRepository(EmergencyBanner::class);
			$updatedBanner = $repository->findOneBannerWithUsername();

			return [
				'success' => true,
				'banner' => $updatedBanner,
				'message' => 'Emergency banner updated successfully'
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'message' => 'Failed to update emergency banner: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Handles emergency notices creation, update, and deletion.
	 * @param array $noticesData Array of notice data from the form
	 */
	private function handleEmergencyNotices(array $noticesData): void
	{
		/** @var EmergencyNoticeRepository $noticeRepository */
		$noticeRepository = $this->em->getRepository(EmergencyNotice::class);

		$existingNotices = $noticeRepository->findAll();
		$existingIds = array_map(fn($notice) => $notice->getId(), $existingNotices);
		$submittedIds = array_filter(array_map(fn($notice) => $notice['id'] ?? null, $noticesData));

		// Delete notices that are no longer in the submitted data
		$idsToKeep = array_filter($submittedIds);
		$noticeRepository->removeNotInList($idsToKeep);

		// Create or update notices
		foreach ($noticesData as $noticeData) {
			if (empty($noticeData['notice'])) {
				continue; // Skip empty notices
			}

			$notice = null;
			if (!empty($noticeData['id'])) {
				// Update existing notice
				$notice = $noticeRepository->find($noticeData['id']);
			}

			if (!$notice) {
				// Create new notice
				$notice = new EmergencyNotice();
				$notice->setCreatedBy(1); // TODO: Get actual current user ID
			}

			$notice->setNotice($noticeData['notice']);
			$notice->setUpdatedBy(1); // TODO: Get actual current user ID

			// Validate the notice
			$errors = $this->validateNotice($notice);
			if (count($errors) === 0) {
				$this->em->persist($notice);
			}
		}
	}
}
