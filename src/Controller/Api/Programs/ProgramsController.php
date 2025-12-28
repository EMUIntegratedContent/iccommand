<?php
namespace App\Controller\Api\Programs;

use App\Entity\Programs\ProgramKeywords;
use App\Entity\Programs\ProgramWebsites;
use App\Service\ProgramsService;
use App\Entity\Programs\Programs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

if(!ini_get('display_errors')){
	ini_set('display_errors', '1');
}
error_reporting(E_ALL);

/**
 * API Catalog Programs Controller
 * This controller manages the programs (and associated websites) with the actions of getting, adding,
 * updating, and deleting.
 */
class ProgramsController extends AbstractFOSRestController{
	private ProgramsService $service;
	private LoggerInterface $logger;
	private ObjectManager $em;
	private ObjectManager $programKeywordManager;
	private SerializerInterface $serializer;

	/**
	 * The constructor of the ProgramsController.
	 * @param ProgramsService $service The service container of this controller.
	 */
	public function __construct(ProgramsService $service, LoggerInterface $logger, ManagerRegistry $doctrine, SerializerInterface $serializer){
		$this->service = $service;
		$this->logger = $logger;
		$this->em = $doctrine->getManager('programs');
		$this->programKeywordManager = $doctrine->getManagerForClass(ProgramKeywords::class);
		$this->serializer = $serializer;
	}

	/**
	 * Get all programs
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/list', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function getProgramsAction(Request $request): Response{
		$page = $request->query->get('page') ?? 1;
		$pageSize = $request->query->get('limit') ?? 25;
		$catalog = $request->query->get('catalog') ?? 'undergraduate';

		$programs = $this->service->getProgramsPagination($page, $pageSize, $catalog);

		$serialized = $this->serializer->serialize($programs, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Filter out programs by name
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/search', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function searchProgramsAction(Request $request): Response{
		$searchTerm = $request->query->get('searchterm');
		$catalog = $request->query->get('catalog') ?? 'undergraduate';

		$programs = $this->service->getProgramsByName($searchTerm, $catalog);

		$serialized = $this->serializer->serialize($programs, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Get all program websites
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/websites', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function getWebsitesAction(Request $request): Response{
		$page = $request->query->get('page') ?? 1;
		$pageSize = $request->query->get('limit') ?? 25;

		$websites = $this->service->getWebsitesPagination($page, $pageSize);

		$serialized = $this->serializer->serialize($websites, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Filter out program websites by name
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/searchwebsites', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function searchWebsitesAction(Request $request): Response{
		$searchTerm = $request->query->get('searchterm');

		$websites = $this->service->searchWebsites($searchTerm);

		$serialized = $this->serializer->serialize($websites, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Get all colleges
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/colleges', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function progCollegesAction(Request $request): Response{
		$colleges = $this->service->getColleges();

		$serialized = $this->serializer->serialize($colleges, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Get all departments
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/departments', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function progDeptsAction(Request $request): Response{
		$depts = $this->service->getDepartments();

		$serialized = $this->serializer->serialize($depts, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Get all program types
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/types', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function progTypesAction(Request $request): Response{
		$progTypes = $this->service->getProgTypes();

		$serialized = $this->serializer->serialize($progTypes, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Get all degrees
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/degrees', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function degreesAction(Request $request): Response{
		$degrees = $this->service->getDegrees();

		$serialized = $this->serializer->serialize($degrees, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Gets the program website by the specified ID.
	 * @param $id // The ID of the website.
	 * @return Response The program, the status code, and the HTTP headers.
	 */
	#[Route('websites/{id}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function getWebsiteAction($id): Response{
		$website = $this->service->getWebsiteEntity($id);
		if(!$website){
			return new Response("The website you requested was not found.", 404, array("Content-Type" => "application/json"));
		}
		$serialized = $this->serializer->serialize($website, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}
	
	/**
	 * Get all keywords
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/keywords', methods: ['GET'])]
	// #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function getKeywordsAction(Request $request): Response{
		$keywords = $this->service->getKeywords();

		$serialized = $this->serializer->serialize($keywords, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Gets the program by the specified ID.
	 * @param $id // The ID of the program.
	 * @return Response The program, the status code, and the HTTP headers.
	 */
	#[Route('/{id}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function getProgramAction($id): Response{
		$program = $this->service->getProgram($id);
		if(!$program){
			return new Response("The program you requested was not found.", 404, array("Content-Type" => "application/json"));
		}
		$serialized = $this->serializer->serialize($program, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Posts the new program from the specified request.
	 * @param Request $request The holder of the information about the new program.
	 * @return Response The program, the status code, and the HTTP headers.
	 */
	#[Route('/', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_CREATE")'))]
	public function postProgramAction(Request $request): Response{
		$catalog = strtolower($request->request->get("catalog"));
		$progFullName = $request->request->get("full_name");
		$progName = $request->request->get("program");
		$url = strtolower($request->request->get("url"));

		$program = new Programs();
		// Get the max id and increment by 1

		$program->setProgram($progName);
		$program->setFullName($progFullName);
		$program->setCatalog($catalog);
		$program->setDepartmentId($request->request->get("department_id"));
		$program->setDegreeId($request->request->get("degree_id"));
		$program->setTypeId($request->request->get("type_id"));
		
		$deliveryIds = $request->request->all("delivery_ids");
		if (is_string($deliveryIds)) {
			$deliveryIds = trim($deliveryIds) === '' ? [] : explode(',', $deliveryIds);
		}
		if (!is_array($deliveryIds)) {
			$deliveryIds = $deliveryIds ? [$deliveryIds] : [];
		}
		$deliveryIds = array_map('intval', $deliveryIds);
		$program->setCollegeId($request->request->get("college_id"));
		$program->setSlug($this->service->makeProgramSlug($progName));
		$program->setCatalogId($this->service->getCatalogIdFromName($catalog));

		$errors = $this->service->validate($program); // Validate the program.

		if (count($errors) > 0) {
				// Do the following if there is more than one error.
				$serialized = $this->serializer->serialize($errors, "json");

				return new Response($serialized, 422, array("Content-Type" => "application/json"));
		}

		$this->em->persist($program); // Persist the program.
		$this->em->flush(); // Commit everything to the database.

		// Update delivery modes using service method
		try {
			$this->service->updateProgramDeliveryModes($program->getId(), $deliveryIds);
		} catch (\Exception $e) {
			throw $e;
		}

		// Update keywords
		$keywordIds = $request->request->all("keyword_ids");
		try {
			$this->service->updateProgramKeywords($program->getId(), $keywordIds);
		} catch (\Exception $e) {
			throw $e;
		}

		// Update the program website information
		$this->service->updateProgWebsite('', $progName, $url);

		$serialized = $this->serializer->serialize($program, "json");

		return new Response($serialized, 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Updates the website from the specified request.
	 * @param Request $request The holder of the information about the updated website.
	 * @return Response The website, the status code, and the HTTP headers.
	 */
	#[Route('/websites/', methods: ['PUT'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_EDIT")'))]
	public function putWebsiteAction(Request $request): Response{
		$id = $request->request->get("id");
		$progName = $request->request->get("program");
		$url = strtolower($request->request->get("url"));

		// Make sure there isn't already a website with the same program name.
		$existing = $this->service->getWebsiteByProg($progName);
		if($existing && $existing->getId() != $id){
			$url = $existing->getUrl();
			return new Response("This program already has a website ($url).", 422, array("Content-Type" => "application/json"));
		}

		$website = $this->service->getWebsiteEntity($id);
		$website->setProgram($progName);
		$website->setUrl($url);

		$this->em->persist($website); // Persist the program.
		$this->em->flush(); // Commit everything to the database.

		$serialized = $this->serializer->serialize($this->service->getWebsiteEntity($id), "json");

		return new Response($serialized, 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Updates the program from the specified request.
	 * @param Request $request The holder of the information about the updated program.
	 * @return Response The program, the status code, and the HTTP headers.
	 */
	#[Route('/', methods: ['PUT'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_EDIT")'))]
	public function putProgramAction(Request $request): Response{
		$id = $request->request->get("id");
		$progFullName = $request->request->get("full_name");
		$progName = $request->request->get("program");
		$catalog = strtolower($request->request->get("catalog"));
		$url = strtolower($request->request->get("url"));

		$program = $this->service->getProgramEntity($id);
		$progOrig = clone $program;
		$origProgName = $progOrig->getProgram();
		$program->setProgram($progName);
		$program->setCatalog($catalog);
		$program->setFullName($progFullName);
		$program->setDepartmentId($request->request->get("department_id"));
		$program->setDegreeId($request->request->get("degree_id"));
		$program->setTypeId($request->request->get("type_id"));
		$program->setCollegeId($request->request->get("college_id"));
		$program->setCatalogId($this->service->getCatalogIdFromName($catalog));

		$errors = $this->service->validate($program); // Validate the program.

		if(count($errors) > 0){
			// Do the following if there is more than one error.
			$serialized = $this->serializer->serialize($errors, "json");

			return new Response($serialized, 422, array("Content-Type" => "application/json"));
		}

		$this->em->persist($program); // Persist the program.
		$this->em->flush(); // Commit everything to the database.

		// handle delivery ids (may be array, CSV string, or single value)
		$deliveryIds = $request->request->all("delivery_ids");

		// Update delivery modes using service method
		try {
			$this->service->updateProgramDeliveryModes($program->getId(), $deliveryIds);
		} catch (\Exception $e) {
			throw $e;
		}

		// Update keywords
		$keywordIds = $request->request->all("keyword_ids");
		try {
			$this->service->updateProgramKeywords($program->getId(), $keywordIds);
		} catch (\Exception $e) {
			throw $e;
		}

		// Update the program website information
		$this->service->updateProgWebsite($origProgName, $progName, $url);

		$serialized = $this->serializer->serialize($this->service->getProgram($id), "json");

		return new Response($serialized, 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Deletes the website from the specified ID.
	 * @param $id // The ID of the website.
	 * @return Response The message of the deleted website, the status code, and the HTTP headers.
	 */
	#[Route('/websites/{id}', methods: ['DELETE'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_DELETE")'))]
	public function deleteWebsiteAction($id): Response{
		$website = $this->service->getWebsiteEntity($id);

		$this->em->remove($website);
		$this->em->flush();

		return new Response("Website has been deleted.", 204, array("Content-Type" => "application/json"));
	}

	/**
	 * Deletes the program from the specified ID.
	 * @param $id // The ID of the program.
	 * @return Response The message of the deleted program, the status code, and the HTTP headers.
	 */
	#[Route('/{id}', methods: ['DELETE'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_DELETE")'))]
	public function deleteProgramAction($id): Response{
		$program = $this->service->getProgramEntity($id);

		$this->em->remove($program);
		$this->em->flush();

		return new Response("Program has been deleted.", 204, array("Content-Type" => "application/json"));
	}

	/**
	 * Create a new keyword
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/keywords', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_CREATE")'))]
	public function postKeywordAction(Request $request): Response{
		$keywordName = $request->request->get("keyword");

		if (empty($keywordName)) {
			return new Response("Keyword name is required.", 422, array("Content-Type" => "application/json"));
		}

		$keyword = $this->service->createKeyword($keywordName);

		$serialized = $this->serializer->serialize($keyword, "json");

		return new Response($serialized, 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Delete a keyword
	 * @param $id
	 * @return Response
	 */
	#[Route('/keywords/{id}', methods: ['DELETE'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_DELETE")'))]
	public function deleteKeywordAction($id): Response{
		$programKeyword = $this->service->getProgramKeywordEntity($id);
		if (!$programKeyword) {
			return new Response("Keyword not found.", 404, array("Content-Type" => "application/json"));
		}

		$this->programKeywordManager->remove($programKeyword);
		$this->programKeywordManager->flush();

		return new Response("Keyword has been deleted.", 204, array("Content-Type" => "application/json"));
	}

	/**
	 * Get all programs linked to a keyword
	 * @param $id The keyword ID
	 * @return Response
	 */
	#[Route('/keywords/{id}/programs', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_VIEW")'))]
	public function getKeywordProgramsAction($id): Response{
		$programKeyword = $this->service->getProgramKeywordEntity($id);
		if (!$programKeyword) {
			return new Response("Keyword not found.", 404, array("Content-Type" => "application/json"));
		}

		$programs = $this->service->getProgramsForKeyword($id);
		$serialized = $this->serializer->serialize($programs, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Link a program to a keyword
	 * @param $id The keyword ID
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/keywords/{id}/programs', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_EDIT")'))]
	public function linkProgramToKeywordAction($id, Request $request): Response{
		$programKeyword = $this->service->getProgramKeywordEntity($id);
		if (!$programKeyword) {
			return new Response("Keyword not found.", 404, array("Content-Type" => "application/json"));
		}

		$programId = $request->request->get("program_id");
		if (!$programId) {
			return new Response("Program ID is required.", 422, array("Content-Type" => "application/json"));
		}

		$program = $this->service->getProgramEntity(intval($programId));
		if (!$program) {
			return new Response("Program not found.", 404, array("Content-Type" => "application/json"));
		}

		try {
			$this->service->linkProgramToKeyword(intval($id), intval($programId));
			return new Response("Program linked successfully.", 201, array("Content-Type" => "application/json"));
		} catch (\Exception $e) {
			return new Response("Error linking program: " . $e->getMessage(), 500, array("Content-Type" => "application/json"));
		}
	}

	/**
	 * Unlink a program from a keyword
	 * @param $id The keyword ID
	 * @param $programId The program ID
	 * @return Response
	 */
	#[Route('/keywords/{id}/programs/{programId}', methods: ['DELETE'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_PROGRAMS_ADMIN") or is_granted("ROLE_PROGRAMS_EDIT")'))]
	public function unlinkProgramFromKeywordAction($id, $programId): Response{
		$programKeyword = $this->service->getProgramKeywordEntity($id);
		if (!$programKeyword) {
			return new Response("Keyword not found.", 404, array("Content-Type" => "application/json"));
		}

		$program = $this->service->getProgramEntity(intval($programId));
		if (!$program) {
			return new Response("Program not found.", 404, array("Content-Type" => "application/json"));
		}

		try {
			$this->service->unlinkProgramFromKeyword(intval($id), intval($programId));
			return new Response("Program unlinked successfully.", 204, array("Content-Type" => "application/json"));
		} catch (\Exception $e) {
			return new Response("Error unlinking program: " . $e->getMessage(), 500, array("Content-Type" => "application/json"));
		}
	}
}

?>
