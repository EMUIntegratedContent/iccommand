<?php

namespace App\Service;

use App\Entity\Directory\Department;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The directory service is used primarily for managing departments, getting department
 * permissions of users, and validating.
 */
class DirectoryService
{
  private AuthorizationCheckerInterface $authorizationChecker;
  private ValidatorInterface $validator;
  private ManagerRegistry $doctrine;
  private EntityManagerInterface $em;

  /**
   * The constructor of the service of the directory.
   */
  public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em)
  {
    $this->authorizationChecker = $authorizationChecker;
    $this->validator = $validator;
    $this->doctrine = $doctrine;
    $this->em = $em;
  }

  /**
   * Removes a department from the database.
   * @param Department $department The department to be removed.
   */
  public function deleteDepartment(Department $department): void
  {
    $this->em->remove($department);
    $this->em->flush();
  }

  /**
   * Fetches the permissions of the user for managing departments.
   * @return array The user's permissions for managing departments.
   */
  public function getUserDepartmentPermissions(): array
  {
    // Set all permissions to false as default.
    $departmentPermissions = array(
      'user' => false,
      'admin' => false
    );

    // The admins automatically have all the permissions.
    if ($this->authorizationChecker->isGranted('ROLE_DIRECTORY_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
      $departmentPermissions['user'] = true;
      $departmentPermissions['admin'] = true;
    }

    // The non-admins have the "user" permission.
    if ($this->authorizationChecker->isGranted('ROLE_DIRECTORY_USER')) {
      $departmentPermissions['user'] = true;
    }

    return $departmentPermissions;
  }

  /**
   * Uses the Symfony container's validator to validate fields for a department.
   * @param Department $department A department entity.
   * @return ConstraintViolationList A list of errors.
   */
  public function validate($department): ConstraintViolationList
  {
    return $this->validator->validate($department);
  }

  /**
   * Gets the departments with pagination.
   * @param $currentPage
   * @param $pageSize
   * @param $searchTerm
   * @return array
   * @throws \Doctrine\ORM\NoResultException
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function getDepartmentsPagination($currentPage, $pageSize, $searchTerm = '')
  {
    // Get the Doctrine repository
    $repository = $this->doctrine->getRepository(Department::class);
    return $repository->paginatedDepartments($currentPage, $pageSize, $searchTerm);
  }

  /**
   * Get departments that match the search term.
   * @param $searchTerm
   * @return array
   * @throws \Doctrine\ORM\NoResultException
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function getDepartmentsByName($searchTerm)
  {
    // Get the Doctrine repository
    $repository = $this->doctrine->getRepository(Department::class);

		$depts = $repository->searchResultsDepartments($searchTerm);

		// Make kebab-case versions of department names for matching

    return $repository->searchResultsDepartments($searchTerm);
  }

	/**
	 * Get departments that match the phone number (used on emich.edu/directory).
	 * @param $phoneNumber
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getDepartmentsByPhone($phoneNumber)
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Department::class);
		return $repository->searchResultsDepartmentsByPhone($phoneNumber);
	}

	/**
	 * Get departments that start with the specified letter.
	 * @param $letter
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getDepartmentsStartWithLetter($letter) {
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Department::class);
		return $repository->searchResultsStartWithLetter($letter);
	}

  /**
   * Normalizes search terms by splitting by @@, trimming, filtering empty values, deduplicating, and joining back
   * Terms are converted to lowercase.
   * @param string|null $searchTerms
   * @return string|null
   */
  public function normalizeSearchTerms(?string $searchTerms): ?string
  {
    if (!$searchTerms) {
      return null;
    }

    // Normalize search terms: split by @@, trim, filter empty, deduplicate, join
    $terms = array_filter(array_map('trim', explode('@@', strtolower($searchTerms))));
    $terms = array_unique($terms);
    return implode('@@', $terms);
  }
}
