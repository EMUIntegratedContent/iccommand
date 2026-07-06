<?php

namespace App\Service;

use App\Entity\Programs\ProgramKeywordLinks;
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
class ProgramsService
{
	private AuthorizationCheckerInterface $authorizationChecker;
	private ValidatorInterface $validator;
	private ObjectManager $em;

	/**
	 * The constructor of the service of the redirects.
	 */
	public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine)
	{
		$this->authorizationChecker = $authorizationChecker;
		$this->validator = $validator;
		$this->em = $doctrine->getManager('programs');
	}

	/**
	 * Uses the Symfony container's validator to validate fields for a program.
	 * @param Programs $program
	 * @return ConstraintViolationList A list of errors.
	 */
	public function validate($program): ConstraintViolationList
	{
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
	 * Faceted Degrees & Programs search backing the public Modern Campus /degrees page.
	 * Ports the WHERE-clause building, pagination and sort logic from the legacy
	 * degrees-search-get-results.php, but normalizes/validates every filter and builds a
	 * parameterized WHERE fragment (the repository runs the actual COUNT + SELECT).
	 *
	 * Accepted params (all optional): program (string), level (array of
	 * 'undergraduate'|'graduate'), degree (array), mode (array of 0|1|2),
	 * department (comma-separated ids), college (int), pathway (truthy), match
	 * ('exact'|'wildcard'), sort ('field.asc'|'field.desc'), page (int).
	 *
	 * @param array $params Raw query params (typically $request->query->all()).
	 * @return array{programs: array, count: int, pages: int, offset: int, end: int, areasOfStudy: array}
	 */
	public function searchDegreePrograms(array $params): array
	{
		$binds = [];
		$where = '';

		// --- Program level -> catalog filter --------------------------------------
		// Legacy net behavior: only-graduate or only-undergraduate narrows p.catalog;
		// both-or-neither applies no level filter.
		$levels = $this->normalizeToArray($params['level'] ?? []);
		$hasUndergrad = in_array('undergraduate', $levels, true);
		$hasGrad = in_array('graduate', $levels, true);
		if ($hasGrad && !$hasUndergrad) {
			$where .= ' AND p.catalog = :catalog';
			$binds['catalog'] = 'graduate';
		} elseif ($hasUndergrad && !$hasGrad) {
			$where .= ' AND p.catalog = :catalog';
			$binds['catalog'] = 'undergraduate';
		}

		// --- MiTransfer Pathways (static predicate, no user input) -----------------
		if (!empty($params['pathway'])) {
			$where .= " AND p.full_name LIKE '%MiTransfer%Pathway%'";
		}

		// --- Keyword / program-name search ----------------------------------------
		$program = isset($params['program']) ? trim((string) $params['program']) : '';
		if ($program !== '') {
			$progFrag = '(p.full_name LIKE :program OR pk.keyword LIKE :program)';
			$exact = (isset($params['match']) && $params['match'] === 'exact');
			$binds['program'] = $exact ? $program : '%' . $program . '%';

			// Legacy "online" convenience: searching for online also matches online-delivery
			// programs. The legacy `class_type` column is gone in this schema; delivery now
			// lives in program_delivery, so test membership there (delivery_id 1 = online).
			if (stripos($program, 'online') !== false || stripos('online', $program) !== false) {
				$progFrag = '((' . $progFrag . ') OR EXISTS (SELECT 1 FROM program_delivery pdx WHERE pdx.program_id = p.id AND pdx.delivery_id = 1))';
			}
			$where .= ' AND ' . $progFrag;
		}

		// --- Area of Study (department ids) ---------------------------------------
		$departmentIds = $this->intList($params['department'] ?? '');
		if ($departmentIds !== []) {
			$list = implode(',', $departmentIds);
			$where .= " AND ((d.id IN ($list)) OR (pid.department_id IN ($list)))";
		}

		// --- College --------------------------------------------------------------
		$college = isset($params['college']) ? (int) $params['college'] : 0;
		if ($college > 0) {
			$where .= ' AND (p.college_id = :college OR cl.college_id = :college_link)';
			$binds['college'] = $college;
			$binds['college_link'] = $college;
		}

		// --- Degree type ----------------------------------------------------------
		$degrees = array_filter(
			array_map('trim', $this->normalizeToArray($params['degree'] ?? [])),
			static fn($d) => $d !== ''
		);
		if ($degrees !== []) {
			$frag = ' AND (FALSE';
			$i = 0;
			foreach ($degrees as $degree) {
				$ph = 'deg' . $i++;
				$frag .= " OR de.degree LIKE :$ph";
				$binds[$ph] = '%' . $degree . '%';
			}
			$frag .= ')';
			$where .= $frag;
		}

		// --- Delivery mode --------------------------------------------------------
		$modes = array_values(array_filter(
			array_map('intval', $this->normalizeToArray($params['mode'] ?? [])),
			static fn($m) => in_array($m, [0, 1, 2], true)
		));
		if ($modes !== []) {
			$where .= ' AND pd.delivery_id IN (' . implode(',', $modes) . ')';
		}

		// --- Sort (whitelisted field + direction) ---------------------------------
		$orderBy = $this->buildOrderBy($params['sort'] ?? null);

		$page = isset($params['page']) ? (int) $params['page'] : 1;
		$limit = 20;

		$result = $this->em->getRepository(Programs::class)
			->searchDegreePrograms($where, $binds, $orderBy, $page, $limit);

		$result['areasOfStudy'] = $this->em->getRepository(Programs::class)->getAreasOfStudy();
		unset($result['page']);

		return $result;
	}

	/**
	 * Coerce a query param into an array of strings (checkbox groups arrive as arrays,
	 * a single selection may arrive as a scalar; empty/null becomes []).
	 * @param mixed $value
	 * @return string[]
	 */
	private function normalizeToArray($value): array
	{
		if (is_array($value)) {
			return $value;
		}
		if ($value === null || $value === '') {
			return [];
		}
		return [$value];
	}

	/**
	 * Parse a comma-separated id string into a list of positive ints (injection-safe).
	 * @param mixed $value
	 * @return int[]
	 */
	private function intList($value): array
	{
		if (is_array($value)) {
			$parts = $value;
		} else {
			$parts = explode(',', (string) $value);
		}
		$ids = array_filter(array_map('intval', $parts), static fn($v) => $v > 0);
		return array_values($ids);
	}

	/**
	 * Build a safe ORDER BY clause from a "field.direction" sort param. Field is
	 * matched against a fixed allowlist and direction is normalized to ASC/DESC, so
	 * no user text reaches the SQL. Defaults to program name ascending.
	 * @param mixed $sort
	 * @return string
	 */
	private function buildOrderBy($sort): string
	{
		$default = ' ORDER BY p.full_name ASC';
		if (!is_string($sort) || $sort === '' || strpos($sort, '.') === false) {
			return $default;
		}

		[$field, $direction] = explode('.', $sort, 2);
		$direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

		switch ($field) {
			case 'program':
				return ' ORDER BY p.full_name ' . $direction;
			case 'department':
				return ' ORDER BY d.department ' . $direction;
			case 'college':
				return ' ORDER BY c.college ' . $direction;
			case 'degree':
				return " ORDER BY IF(de.degree LIKE '%certificate%', 'Certificate', de.degree) " . $direction;
			case 'mode':
				// class_type is gone in this schema; approximate mode ordering by the
				// lowest delivery id present for the program.
				return ' ORDER BY MIN(pd.delivery_id) ' . $direction;
			default:
				return $default;
		}
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
	public function getDegrees()
	{
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
	 * A single website by program ID.
	 * @param int $programId
	 * @return ?ProgramWebsites
	 */
	public function getWebsiteByProgramId(int $programId): ?ProgramWebsites
	{
		$repository = $this->em->getRepository(ProgramWebsites::class);
		return $repository->getWebsiteByProgramId($programId);
	}

	/**
	 * Return a single program with join data for website info
	 * @param $id
	 * @return Programs
	 */
	public function getProgram($id)
	{
		// Get the Doctrine repository
		$repository = $this->em->getRepository(Programs::class);
		return $repository->getProgram($id);
	}

	/**
	 * Get a Program entity by ID (used when editing; the programs entity manager must be used explicitly.)
	 * @param $id
	 * @return mixed
	 */
	public function getProgramEntity($id)
	{
		$repository = $this->em->getRepository(Programs::class);
		return $repository->getProgramEntity($id);
	}

	/**
	 * Find a Program entity by its program name (the 'program' field).
	 * @param string $progName
	 * @return Programs|null
	 */
	public function findProgramByProgramName(string $progName): ?Programs
	{
		$repository = $this->em->getRepository(Programs::class);
		return $repository->findOneByProgramName($progName);
	}

	/**
	 * Get a ProgramWebsites entity by ID (used when editing; the programs entity manager must be used explicitly.)
	 * @param $id
	 * @return mixed
	 */
	public function getWebsiteEntity($id)
	{
		$repository = $this->em->getRepository(ProgramWebsites::class);
		return $repository->getWebsiteEntity($id);
	}

	/**
	 * Persist and flush a ProgramWebsites entity (uses programs entity manager).
	 * @param ProgramWebsites $website
	 * @return void
	 */
	public function saveWebsite(ProgramWebsites $website): void
	{
		$this->em->persist($website);
		$this->em->flush();
	}

	/**
	 * Update a program's website information in program_websites
	 * @param Programs $program
	 * @param string|null $url
	 * @return ?ProgramWebsites
	 */
	public function updateProgWebsite(Programs $program, $url = null): ?ProgramWebsites
	{
		// Case 1. If there is a program without a URL. See if there is a website record with that program id and remove the record
		// Case 2. If there is a program with a URL. See if there is a website record with that program id. If there is, update the record. If there isn't, create a new record.

		// Strip out any host information from the URL
		if ($url) {
			$parsedUrl = parse_url($url);

			if (array_key_exists("host", $parsedUrl) && preg_match("/emich\.edu/", $parsedUrl["host"])) {
				if ($parsedUrl["path"][0] != "/") {
					$url = "/" . $parsedUrl["path"];
				} else {
					$url = $parsedUrl["path"];
				}
			} else if (!array_key_exists("host", $parsedUrl) && $parsedUrl["path"][0] != "/") {
				$url = "/" . $parsedUrl["path"];
			}
		}

		$website = $this->getWebsiteByProgramId($program->getId());

		if ($website && !$url) {
			$this->em->remove($website);
		} else if ($website && $url) {
			$website->setProgram($program->getProgram());
			$website->setProgramRef($program);
			$website->setUrl($url);
			$this->em->persist($website);
		} else if (!$website && $url) {
			$website = new ProgramWebsites();
			$website->setProgram($program->getProgram());
			$website->setProgramRef($program);
			$website->setUrl($url);
			$this->em->persist($website);
		}
		$this->em->flush();

		return $this->getWebsiteByProgramId($program->getId());
	}

	/**
	 * Each year, the ids for undergraduate and graduate catalogs change. Return the id of the catalog based on the catalog name passed.
	 * @param $catalog
	 * @return int|null
	 */
	public function getCatalogIdFromName($catalog)
	{
		$repository = $this->em->getRepository(Programs::class);
		$catRows = $repository->getCatalogIds();
		$catId = null;
		foreach ($catRows as $catRow) {
			if ($catRow['catalog'] == $catalog) {
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
	public function makeProgramSlug($programName)
	{
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
	public function updateProgramDeliveryModes(int $programId, $deliveryIds): void
	{
		if (is_string($deliveryIds)) {
			$deliveryIds = trim($deliveryIds) === '' ? [] : explode(',', $deliveryIds);
		}
		if (!is_array($deliveryIds)) {
			$deliveryIds = $deliveryIds ? [$deliveryIds] : [];
		}
		$deliveryIds = array_map('intval', $deliveryIds);

		$this->em->getRepository(Programs::class)->updateProgramDeliveryModes($programId, $deliveryIds);
	}

	/**
	 * Get keywords with pagination and optional search.
	 * @param int $page
	 * @param int $limit
	 * @param string|null $searchTerm
	 * @return array
	 */
	public function getKeywordsPagination(int $page, int $limit, ?string $searchTerm = null): array
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);
		return $repository->findAllWithProgramCountPagination($page, $limit, $searchTerm);
	}

	/**
	 * Get a ProgramKeyword entity by ID.
	 * @param int $id
	 * @return ?ProgramKeywords
	 */
	public function getProgramKeywordEntity(int $id): ?ProgramKeywords
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);
		return $repository->getKeywordEntity($id);
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
	 * Bulk-create keywords from parsed CSV rows.
	 *
	 * Each row is an associative array with a required 'keyword' and an optional
	 * 'program_id'. Per-row rules:
	 *  - blank keyword            -> rejected (nothing created)
	 *  - keyword already exists   -> skipped (nothing created, no link)
	 *  - new keyword, no id       -> keyword created
	 *  - new keyword, valid id    -> keyword created + linked to program
	 *  - new keyword, bad id      -> keyword created, link skipped (program not found)
	 *
	 * @param array $rows
	 * @return array{created: string[], skipped: string[], rejected: int, linkSkipped: string[]}
	 */
	public function bulkCreateKeywords(array $rows): array
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);

		$created = [];
		$skipped = [];
		$linkSkipped = [];
		$rejected = 0;

		foreach ($rows as $row) {
			$keywordName = isset($row['keyword']) ? trim((string) $row['keyword']) : '';
			if ($keywordName === '') {
				++$rejected;
				continue;
			}

			// Duplicate check (case-insensitive). Also catches keywords repeated
			// within the same CSV, since createKeyword() flushes per call.
			if ($repository->findOneByKeyword($keywordName)) {
				$skipped[] = $keywordName;
				continue;
			}

			$keyword = $this->createKeyword($keywordName);
			$created[] = $keywordName;

			$programId = isset($row['program_id']) ? trim((string) $row['program_id']) : '';
			if ($programId !== '') {
				$program = $this->getProgramEntity(intval($programId));
				if ($program) {
					$this->linkProgramToKeyword($keyword->getId(), intval($programId));
				} else {
					$linkSkipped[] = $keywordName;
				}
			}
		}

		return [
			'created' => $created,
			'skipped' => $skipped,
			'rejected' => $rejected,
			'linkSkipped' => $linkSkipped,
		];
	}

	/**
	 * Delete a keyword and cascade delete from links.
	 * @param int $id
	 * @return void
	 */
	public function deleteKeyword(int $id): void
	{
		$programKeyword = $this->getProgramKeywordEntity($id);
		$this->em->remove($programKeyword);
		$this->em->flush();

		// Also remove any links
		$repository = $this->em->getRepository(ProgramKeywordLinks::class);
		$repository->deleteByKeywordId($id);
	}

	/**
	 * Normalize and update colleges for a program by delegating to the repository.
	 * Accepts an array or comma-separated string and ensures an array of ints is passed
	 * to the repository.
	 *
	 * @param int $programId
	 * @param mixed $collegeIds
	 * @return void
	 * @throws \Exception
	 */
	public function updateProgramColleges(int $programId, $collegeIds): void
	{
		if (is_string($collegeIds)) {
			$collegeIds = trim($collegeIds) === '' ? [] : explode(',', $collegeIds);
		}
		if (!is_array($collegeIds)) {
			$collegeIds = $collegeIds ? [$collegeIds] : [];
		}
		$collegeIds = array_map('intval', $collegeIds);

		$this->em->getRepository(Programs::class)->updateProgramColleges($programId, $collegeIds);
	}

	/**
	 * Normalize and update departments for a program by delegating to the repository.
	 * Accepts an array or comma-separated string and ensures an array of ints is passed
	 * to the repository.
	 *
	 * @param int $programId
	 * @param mixed $departmentIds
	 * @return void
	 * @throws \Exception
	 */
	public function updateProgramDepartments(int $programId, $departmentIds): void
	{
		if (is_string($departmentIds)) {
			$departmentIds = trim($departmentIds) === '' ? [] : explode(',', $departmentIds);
		}
		if (!is_array($departmentIds)) {
			$departmentIds = $departmentIds ? [$departmentIds] : [];
		}
		$departmentIds = array_map('intval', $departmentIds);

		$this->em->getRepository(Programs::class)->updateProgramDepartments($programId, $departmentIds);
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

	/**
	 * Get all programs linked to a keyword.
	 * @param int $keywordId
	 * @return array
	 */
	public function getProgramsForKeyword(int $keywordId): array
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);
		return $repository->getProgramsForKeyword($keywordId);
	}

	/**
	 * Link a program to a keyword.
	 * @param int $keywordId
	 * @param int $programId
	 * @return void
	 */
	public function linkProgramToKeyword(int $keywordId, int $programId): void
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);
		$repository->linkProgramToKeyword($keywordId, $programId);
	}

	/**
	 * Unlink a program from a keyword.
	 * @param int $keywordId
	 * @param int $programId
	 * @return void
	 */
	public function unlinkProgramFromKeyword(int $keywordId, int $programId): void
	{
		$repository = $this->em->getRepository(ProgramKeywords::class);
		$repository->unlinkProgramFromKeyword($keywordId, $programId);
	}
}
