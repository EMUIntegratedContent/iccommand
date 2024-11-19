<?php
namespace App\Service;

use App\Entity\Programs\Programs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The programs service is used primarily for CRUD actions on Catalog Programs
 */
class ProgramsService {
  private AuthorizationCheckerInterface $authorizationChecker;
  private ValidatorInterface $validator;
  private ManagerRegistry $doctrine;
  private EntityManagerInterface $em;

  /**
   * The constructor of the service of the redirects.
   */
  public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em) {
    $this->authorizationChecker = $authorizationChecker;
    $this->validator = $validator;
    $this->doctrine = $doctrine;
    $this->em = $em;
  }

//  /**
//   * Removes a redirect from the database.
//   * @param Redirect $redirect The redirect to be removed.
//   */
//  public function deleteRedirect(Redirect $redirect): void
//  {
//    $this->em->remove($redirect);
//    $this->em->flush();
//  }

	/**
	 * Uses the Symfony container's validator to validate fields for a program.
	 * @param Programs $program
	 * @return ConstraintViolationList A list of errors.
	 */
  public function validate($program): ConstraintViolationList {
    return $this->validator->validate($program);
  }

	/**
	 * Fetches the permissions of the user for managing programs.
	 * @return array The user's permissions for managing programs.
	 */
	#[ArrayShape(['user' => "bool", 'admin' => "bool"])]
	public function getProgramsPermissions(): array
	{
		// Set all permissions to false as default.
		$programsPermissions = array(
			'user' => true, // set to false before launch
			'admin' => true // set to false before launch
		);

//		// The admins automatically have all the permissions.
//		if ($this->authorizationChecker->isGranted('ROLE_REDIRECT_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
//			$redirectPermissions['user'] = true;
//			$redirectPermissions['admin'] = true;
//		}
//
//		// The non-admins have the "user" permission.
//		if ($this->authorizationChecker->isGranted('ROLE_REDIRECT_USER')) {
//			$redirectPermissions['user'] = true;
//		}

		return $programsPermissions;
	}

	/**
	 * Gets the programs with pagination.
	 * @param $currentPage
	 * @param $pageSize
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getProgramsPagination($currentPage, $pageSize)
	{
		// Calculate the offset
		$offset = ($currentPage - 1) * $pageSize;

		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Programs::class);

		// Build the query for getting paginated records
		$queryBuilder = $repository->createQueryBuilder('p')
			->orderBy('p.program', 'ASC')
			->setFirstResult($offset)
			->setMaxResults($pageSize);

		$query = $queryBuilder->getQuery();

		// Execute the query to get paginated records
		$programs = $query->getResult();

		// Count the total number of rows
		$totalBrokenQueryBuilder = $repository->createQueryBuilder('p')
			->select('COUNT(p.id)');

		$totalProgQuery = $totalBrokenQueryBuilder->getQuery();

		$totalProgs = $totalProgQuery->getSingleScalarResult();

		// Return both paginated results and total count
		return [
			'programs' => $programs,
			'totalRows' => $totalProgs
		];
	}
}
