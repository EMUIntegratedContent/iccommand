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

//	/**
//	 * Get programs that are unaffiliated with a website.
//	 * @return array
//	 * @throws \Doctrine\ORM\NoResultException
//	 * @throws \Doctrine\ORM\NonUniqueResultException
//	 */
//	public function getWebsitesUnaffiliated() {
//		$repository = $this->doctrine->getRepository(ProgramWebsites::class);
//		return $repository->unaffiliatedProgs();
//	}

	/**
	 * Get all colleges.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getColleges()
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Programs::class);
		return $repository->getColleges();
	}

	/**
	 * Get all departments.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getDepartments()
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Programs::class);
		return $repository->getDepartments();
	}

	/**
	 * Get all program types.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getProgTypes()
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Programs::class);
		return $repository->getProgTypes();
	}

	/**
	 * Get all degrees.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getDegrees() {
		$repository = $this->doctrine->getRepository(Programs::class);
		return $repository->getDegrees();
	}

	/**
	 * Get websites based on LIKE program name.
	 * @param $searchTerm
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function searchWebsites($searchTerm)
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(ProgramWebsites::class);
		return $repository->searchResults($searchTerm);
	}

	/**
	 * A single website based on the exact program name.
	 * @param $progName
	 * @return ?ProgramWebsites
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getWebsiteByProg($progName)
	{
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(ProgramWebsites::class);
		return $repository->getWebsiteByProg($progName);
	}

	/**
	 * Return a single program with join data for website info
	 * @param $id
	 * @return Programs
	 */
	public function getProgram($id) {
		// Get the Doctrine repository
		$repository = $this->doctrine->getRepository(Programs::class);
		return $repository->getProgram($id);
	}

	/**
	 * Return a single program website
	 * @param $id
	 * @return ProgramWebsites
	 */
	public function getWebsite($id) {
		return $this->doctrine->getRepository(ProgramWebsites::class)->find($id);
	}

	/**
	 * Update a program's website information in program_websites
	 * @param $origName
	 * @param $newName
	 * @param $url
	 * @return ?ProgramWebsites
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function updateProgWebsite($origName, $newName, $url = null): ?ProgramWebsites{
		// Case 1. If there is a program without a URL. See if there is a website record with that program name and remove the record
		// Case 2. If there is a program with a URL. See if there is a website record with that program name. If there is, update the record. If there isn't, create a new record.

		// Strip out any host information from the URL
		if($url){
			$parsedUrl = parse_url($url);

			if(array_key_exists("host", $parsedUrl) && preg_match("/emich\.edu/", $parsedUrl["host"])){
				if($parsedUrl["path"][0] != "/"){
					$url = "/".$parsedUrl["path"];
				}
				else{
					$url = $parsedUrl["path"];
				}
			}
			else if(!array_key_exists("host", $parsedUrl) && $parsedUrl["path"][0] != "/"){
				$url = "/".$parsedUrl["path"];
			}
		}

		$website = null;
		if($origName !== ''){
			$website = $this->getWebsiteByProg($origName);
		}

		if($website && !$url){
			$this->em->remove($website);
		} else if ($website && $url) {
			$website->setProgram($newName);
			$website->setUrl($url);
			$this->em->persist($website);
		} else if(!$website && $url) {
			$website = new ProgramWebsites();
			$website->setProgram($newName);
			$website->setUrl($url);
			$this->em->persist($website);
		}
		$this->em->flush();

		return $this->getWebsiteByProg($newName);
	}

	/**
	 * Each year, the ids for undergraduate and graduate catalogs change. Return the id of the catalog based on the catalog name passed.
	 * @param $catalog
	 * @return int|null
	 */
	public function getCatalogIdFromName ($catalog) {
		$repository = $this->doctrine->getRepository(Programs::class);
		$catRows = $repository->getCatalogIds();
		$catId = null;
		foreach($catRows as $catRow){
			if($catRow['catalog'] == $catalog){
				$catId = $catRow['catalog_id'];
			}
		}
		return $catId;
	}

	/**
	 * Create a slug based on the name of the program.
	 * @param $programName
	 * @return string
	 */
	public function makeProgramSlug($programName){
		$slug = strtolower($programName);
		$slug = preg_replace('/[^a-z0-9]/', '-', $slug);
		$slug = preg_replace('/-+/', '-', $slug);
		$slug = trim($slug, '-');
		return $slug;
	}
}
