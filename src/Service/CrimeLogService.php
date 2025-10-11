<?php

namespace App\Service;

use App\Entity\CrimeLog\CrimeLog;
use App\Entity\CrimeLog\FireLog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\PseudoTypes\ArrayShape;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The crimelog service is used primary for deleting crimelogs, getting crimelog
 * permissions of users, and validating.
 */
class CrimeLogService
{
  private AuthorizationCheckerInterface $authorizationChecker;
  private ValidatorInterface $validator;
  private ManagerRegistry $doctrine;
  private ObjectManager $em;

  /**
   * The constructor of the service of the crimelogs.
   */
  public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em)
  {
    $this->authorizationChecker = $authorizationChecker;
    $this->validator = $validator;
    $this->doctrine = $doctrine;
    $this->em = $em;
  }

  /**
   * Fetches the permissions of the user for managing crimelogs.
   * @return array The user's permissions for managing crimelogs.
   */
	#[ArrayShape(['user' => "bool", 'admin' => "bool"])]
  public function getUserCrimeLogPermissions(): array
  {
    // Set all permissions to false as default.
    $crimelogPermissions = array(
      'user' => false,
      'admin' => false
    );

    // The admins automatically have all the permissions.
    if ($this->authorizationChecker->isGranted('ROLE_CRIMELOG_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
      $crimelogPermissions['user'] = true;
      $crimelogPermissions['admin'] = true;
    }

    // The non-admins have the "user" permission.
    if ($this->authorizationChecker->isGranted('ROLE_CRIMELOG_USER')) {
      $crimelogPermissions['user'] = true;
    }

    return $crimelogPermissions;
  }

	/**
	 * Uses the Symfony container's validator to validate fields for a FireLog.
	 * @param CrimeLog $log
	 * @return ConstraintViolationList A list of errors.
	 */
	public function validate(CrimeLog $log): ConstraintViolationList {
		return $this->validator->validate($log);
	}

	/**
	 * Uses the Symfony container's validator to validate fields for a FireLog.
	 * @param FireLog $log
	 * @return ConstraintViolationList A list of errors.
	 */
	public function validateFireLog(FireLog $log): ConstraintViolationList {
		return $this->validator->validate($log);
	}

  /**
   * Truncates the crimelog table. Should be done before every bulk upload
   */
  public function truncateCrimeLogTable(): void
  {
		$repository = $this->em->getRepository(CrimeLog::class);
		$repository->truncate();
  }

  public function deleteFireLogById(int $id): void
  {
		$repository = $this->em->getRepository(FireLog::class);
		$repository->deleteById($id);
  }

  public function findFireLogByIncidentNumber(string $incidentNumber): ?FireLog
  {
		$repository = $this->em->getRepository(FireLog::class);
		return $repository->findByIncidentNumber($incidentNumber);
  }
}
