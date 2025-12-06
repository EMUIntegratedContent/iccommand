<?php
namespace App\Service;

use App\Entity\Programs\Programs;
use App\Entity\Programs\ProgramWebsites;
use App\Entity\Programs\ProgramKeywords;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The programs service is used primarily for CRUD actions on Catalog Programs
 */
class ProgramsService {
  private AuthorizationCheckerInterface $authorizationChecker;
  private ValidatorInterface $validator;
  private ObjectManager $em;

  /**
   * The constructor of the service of the redirects.
   */
  public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine) {
    $this->authorizationChecker = $authorizationChecker;
    $this->validator = $validator;
    $this->em = $doctrine->getManager('programs');
  }

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
			'admin' => false,
			'create' => false,
			'edit' => false,
			'delete' => false,
			'view' => false,
		);

		// The admins automatically have all the permissions.
		if ($this->authorizationChecker->isGranted('ROLE_PROGRAMS_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
			$programsPermissions['admin'] = true;
			$programsPermissions['create'] = true;
			$programsPermissions['edit'] = true;
			$programsPermissions['delete'] = true;
			$programsPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PROGRAMS_DELETE')) {
			$programsPermissions['create'] = true;
			$programsPermissions['edit'] = true;
			$programsPermissions['delete'] = true;
			$programsPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PROGRAMS_CREATE')) {
			$programsPermissions['create'] = true;
			$programsPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PROGRAMS_EDIT')) {
			$programsPermissions['edit'] = true;
			$programsPermissions['view'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_PROGRAMS_VIEW')) {
			$programsPermissions['view'] = true;
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
		$repository = $this->em->getRepository(Programs::class);
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
		$repository = $this->em->getRepository(Programs::class);
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
		$repository = $this->em->getRepository(ProgramWebsites::class);
		return $repository->paginatedWebsites($currentPage, $pageSize);
	}

	/**
	 * Get all colleges.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getColleges()
	{
		// Get the Doctrine repository
		$repository = $this->em->getRepository(Programs::class);
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
		$repository = $this->em->getRepository(Programs::class);
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
		$repository = $this->em->getRepository(Programs::class);
		return $repository->getProgTypes();
	}

	/**
	 * Get all degrees.
	 * @return array
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function getDegrees() {
		$repository = $this->em->getRepository(Programs::class);
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
		$repository = $this->em->getRepository(ProgramWebsites::class);
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
		$repository = $this->em->getRepository(ProgramWebsites::class);
		return $repository->getWebsiteByProg($progName);
	}

	/**
	 * Return a single program with join data for website info
	 * @param $id
	 * @return Programs
	 */
	public function getProgram($id) {
		// Get the Doctrine repository
		$repository = $this->em->getRepository(Programs::class);
		return $repository->getProgram($id);
	}

	/**
	 * Get a Program entity by ID (used to locate a program for editing because Symfony + Doctrine are too stupid to know which connection to use...)
	 * @param $id
	 * @return mixed
	 */
	public function getProgramEntity($id) {
		$repository = $this->em->getRepository(Programs::class);
		return $repository->getProgramEntity($id);
	}

	/**
	 * Get a ProgramWebsites entity by ID (used to locate a program for editing because Symfony + Doctrine are too stupid to know which connection to use...)
	 * @param $id
	 * @return mixed
	 */
	public function getWebsiteEntity($id) {
		$repository = $this->em->getRepository(ProgramWebsites::class);
		return $repository->getWebsiteEntity($id);
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
		if($origName != ''){
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
		$repository = $this->em->getRepository(Programs::class);
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

	/**
	 * Normalize and update delivery modes for a program by delegating to the repository.
	 * Accepts an array or comma-separated string and ensures an array of ints is passed
	 * to the repository.
	 *
	 * @param int $programId
	 * @param mixed $deliveryIds
	 * @return void
	 * @throws \Exception
	 */
	public function updateProgramDeliveryModes(int $programId, $deliveryIds): void {
		if (is_string($deliveryIds)) {
			$deliveryIds = trim($deliveryIds) === '' ? [] : explode(',', $deliveryIds);
		}
		if (!is_array($deliveryIds)) {
			$deliveryIds = $deliveryIds ? [$deliveryIds] : [];
		}
		$deliveryIds = array_map('intval', $deliveryIds);

		$repository = $this->em->getRepository(Programs::class);
		$repository->updateProgramDeliveryModes($programId, $deliveryIds);
	}

	/**
	 * Get all keywords.
	 * @return array
	 */
	public function getKeywords(): array
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);
		return $repository->findAll();
	}

	/**
	 * Create a new keyword.
	 * @param string $keywordName
	 * @return ProgramKeywords
	 */
	public function createKeyword(string $keywordName): ProgramKeywords
	{
		$keyword = new ProgramKeywords();
		$keyword->setKeyword($keywordName);
		$this->em->persist($keyword);
		$this->em->flush();
		return $keyword;
	}

	/**
	 * Delete a keyword and cascade delete from links.
	 * @param int $id
	 * @return void
	 */
	public function deleteKeyword(int $id): void
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);
		$keyword = $repository->find($id);
		if ($keyword) {
			// Delete all links first
			$conn = $this->em->getConnection();
			$delSql = 'DELETE FROM programs.program_keyword_links WHERE keyword_id = :keyword_id';
			$stmt = $conn->prepare($delSql);
			$stmt->executeStatement(['keyword_id' => $id]);
			
			$this->em->remove($keyword);
			$this->em->flush();
		}
	}

	/**
	 * Normalize and update keywords for a program by delegating to the repository.
	 * Accepts an array or comma-separated string and ensures an array of ints is passed
	 * to the repository.
	 *
	 * @param int $programId
	 * @param mixed $keywordIds
	 * @return void
	 * @throws \Exception
	 */
	public function updateProgramKeywords(int $programId, $keywordIds): void
	{
		if (is_string($keywordIds)) {
			$keywordIds = trim($keywordIds) === '' ? [] : explode(',', $keywordIds);
		}
		if (!is_array($keywordIds)) {
			$keywordIds = $keywordIds ? [$keywordIds] : [];
		}
		$keywordIds = array_map('intval', $keywordIds);

		$repository = $this->em->getRepository(Programs::class);
		$repository->updateProgramKeywords($programId, $keywordIds);
	}
}
