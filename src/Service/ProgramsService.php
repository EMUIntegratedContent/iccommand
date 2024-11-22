<?php
namespace App\Service;

use App\Entity\Programs\Programs;
use App\Entity\Programs\ProgramWebsites;
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
			'user' => false,
			'admin' => false
		);

		// The admins automatically have all the permissions.
		if ($this->authorizationChecker->isGranted('ROLE_PROGRAMS_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
			$programsPermissions['user'] = true;
			$programsPermissions['admin'] = true;
		}

		// The non-admins have the "user" permission.
		if ($this->authorizationChecker->isGranted('ROLE_PROGRAMS_USER')) {
			$programsPermissions['user'] = true;
		}

		return $programsPermissions;
	}

	/**
	 * Gets the programs with pagination for the given catalog.
	 * @param $currentPage
	 * @param $pageSize
	 * @param $catalog
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	#[ArrayShape(['programs' => "array", 'totalRows' => "integer"])]
	public function getProgramsPagination($currentPage, $pageSize, $catalog)
	{
		$repository = $this->doctrine->getRepository(Programs::class);
		return $repository->paginatedPrograms($currentPage, $pageSize, $catalog);
	}

	/**
	 * Get programs that are like the passed progStr for the given catalog.
	 * @param $searchTerm
	 * @param $catalog
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getProgramsByName($searchTerm, $catalog)
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Programs::class);
		return $repository->searchResults($searchTerm, $catalog);
	}

	/**
	 * Gets the websites with pagination.
	 * @param $currentPage
	 * @param $pageSize
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	#[ArrayShape(['websites' => "array", 'totalRows' => "integer"])]
	public function getWebsitesPagination($currentPage, $pageSize)
	{
		$repository = $this->doctrine->getRepository(ProgramWebsites::class);
		return $repository->paginatedWebsites($currentPage, $pageSize);
	}

	/**
	 * Get websites based on the program name.
	 * @param $searchTerm
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getWebsitesByProg($searchTerm)
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(ProgramWebsites::class);
		return $repository->searchResults($searchTerm);
	}
}
