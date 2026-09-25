<?php

namespace App\Service;

use App\Entity\Emergency\EmergencyBanner;
use App\Entity\Emergency\EmergencyNotice;
use App\Entity\Emergency\EmergencySeverity;
use App\Repository\Emergency\EmergencyRepository;
use App\Repository\Emergency\EmergencyNoticeRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The emergency service is used primarily for CRUD actions on Emergency Banners and Notices
 */
class EmergencyService
{
	private AuthorizationCheckerInterface $authorizationChecker;
	private ValidatorInterface $validator;
	private EntityManagerInterface $em;

	/**
	 * The constructor of the service of the emergency banners and notices.
	 */
	public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, private Security $security)
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
	 * Updates the emergency banner, and the notices when the request includes them.
	 * Everything is saved in one transaction, so a failure leaves the old banner and notices intact.
	 * @param array $data The banner data to update.
	 * @return array Result with success status, banner, message, and an HTTP status for failures.
	 */
	public function updateBanner(array $data): array
	{
		$severity = null;
		if (($data['severity'] ?? '') !== '' && $data['severity'] !== null) {
			$severity = EmergencySeverity::tryFrom((string) $data['severity']);
			if ($severity === null) {
				return [
					'success' => false,
					'status' => 422,
					'message' => 'Invalid severity. Use one of: ' . implode(', ', array_column(EmergencySeverity::cases(), 'value')) . '.'
				];
			}
		}

		$notices = $data['notices'] ?? null;
		if ($notices !== null && !is_array($notices)) {
			return ['success' => false, 'status' => 422, 'message' => 'Notices must be a list.'];
		}

		$userId = $this->currentUserId();

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
			$banner->setSeverity($severity);
			$banner->setBannerMessage($data['bannerMessage'] ?? null);
			$banner->setBannerTitle($data['bannerTitle'] ?? null);
			$banner->setUpdatedBy($userId);

			$errorMessages = [];
			foreach ($this->validateBanner($banner) as $error) {
				$errorMessages[] = $error->getMessage();
			}
			if ($errorMessages) {
				$this->em->clear();
				return [
					'success' => false,
					'status' => 422,
					'message' => 'Validation failed: ' . implode(', ', $errorMessages)
				];
			}

			$this->em->wrapInTransaction(function () use ($banner, $notices, $userId) {
				// A request without a "notices" key leaves the notices alone. An empty list clears them.
				if ($notices !== null) {
					$this->reconcileNotices($notices, $userId);
				}
				$this->em->persist($banner);
			});

			// Return updated banner with username
			$this->em->clear();
			/** @var EmergencyRepository $repository */
			$repository = $this->em->getRepository(EmergencyBanner::class);
			$updatedBanner = $repository->findOneBannerWithUsername();

			return [
				'success' => true,
				'banner' => $updatedBanner,
				'message' => 'Emergency banner updated successfully'
			];
		} catch (\InvalidArgumentException $e) {
			// A notice failed validation; the transaction was rolled back.
			return ['success' => false, 'status' => 422, 'message' => $e->getMessage()];
		}
	}

	/**
	 * Creates, updates, and deletes notices to match the submitted list. Must run inside a transaction.
	 * @param array $noticesData Array of notice data from the form
	 * @throws \InvalidArgumentException when a notice fails validation
	 */
	private function reconcileNotices(array $noticesData, int $userId): void
	{
		/** @var EmergencyNoticeRepository $noticeRepository */
		$noticeRepository = $this->em->getRepository(EmergencyNotice::class);

		$keep = [];
		foreach ($noticesData as $noticeData) {
			if (!is_array($noticeData) || trim((string) ($noticeData['notice'] ?? '')) === '') {
				continue; // Skip empty notices
			}

			$notice = !empty($noticeData['id']) ? $noticeRepository->find((int) $noticeData['id']) : null;
			if (!$notice) {
				$notice = new EmergencyNotice();
				$notice->setCreatedBy($userId);
			}
			$notice->setNotice((string) $noticeData['notice']);
			$notice->setUpdatedBy($userId);

			$errors = $this->validateNotice($notice);
			if (count($errors) > 0) {
				throw new \InvalidArgumentException('Invalid notice: ' . $errors[0]->getMessage());
			}
			$this->em->persist($notice);
			$keep[] = $notice;
		}

		// Flush first so new notices have ids, then delete the ones that were removed.
		$this->em->flush();
		$noticeRepository->removeNotInList(array_map(fn (EmergencyNotice $n) => $n->getId(), $keep));
	}

	private function currentUserId(): int
	{
		$user = $this->security->getUser();

		return $user instanceof User ? (int) $user->getId() : 0;
	}
}
