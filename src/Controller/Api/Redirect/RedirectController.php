<?php

namespace App\Controller\Api\Redirect;

use App\Util\RequestHelper;
use App\Entity\Redirect\Redirect;
use App\Service\LinkChecker;
use App\Service\RedirectService;
use App\Service\RedirectUrlNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use App\Security\ExternalApiToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * API Redirect Controller
 * This controller manages the redirects with the actions of getting, adding,
 * updating, and deleting.
 */
class RedirectController extends AbstractController{
	private RedirectService $service;
	private LoggerInterface $logger;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;
	private SerializerInterface $serializer;

	/**
	 * The constructor of the RedirectController.
	 * @param RedirectService $service The service container of this controller.
	 */
	public function __construct(
		RedirectService $service,
		LoggerInterface $logger,
		ManagerRegistry $doctrine,
		EntityManagerInterface $em,
		SerializerInterface $serializer,
		private RedirectUrlNormalizer $urls,
		private LinkChecker $linkChecker,
	){
		$this->service = $service;
		$this->logger = $logger;
		$this->doctrine = $doctrine;
		$this->em = $em;
		$this->serializer = $serializer;
	}

	/**
	 * Find a redirect URL passed from an external source
	 * @param Request $request
	 * @return Response
	 */
	#[Route('external/redirect', methods: ['GET'])]
	public function getExternalRedirectAction(Request $request): Response{
		$url = $request->query->get('url');

		$redirect = $this->doctrine->getRepository(Redirect::class)->findOneBy(['fromLink' => $url]);

		if(!$redirect){
			return new Response(json_encode("The redirect you requested was not found."), Response::HTTP_NOT_FOUND, array('Content-Type' => 'application/json'));
		}

		return new Response(json_encode($redirect->getToLink()), Response::HTTP_OK, array('Content-Type' => 'application/json'));
	}

	/**
	 * Increment the number of visits a URL redirect has received
	 * @param Request $request
	 * @return Response
	 */
	#[Route('external/redirect', methods: ['PUT'])]
	public function putExternalRedirectincrementAction(Request $request, ExternalApiToken $apiToken): Response{
		if(!$apiToken->allows($request, true)){
			return new Response(json_encode('Missing or invalid API token.'), 401, array('Content-Type' => 'application/json'));
		}

		$url = (string) $request->request->get('url');

		// Atomic increment that bypasses the ORM, so the Blameable listener no
		// longer blanks the redirect's "changed by" value on every visit.
		if($url === '' || !$this->doctrine->getRepository(Redirect::class)->incrementVisits($url)){
			return new Response(json_encode("The redirect you requested was not found."), 404, array('Content-Type' => 'application/json'));
		}

		return new Response('Incremented visits to URL '.$url.'.', 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Deletes the redirect from the specified ID.
	 * @param $id // The ID of the redirect.
	 * @return Response The message of the deleted redirect, the status code, and the HTTP headers.
	 */
	#[Route('/{id}', methods: ['DELETE'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_REDIRECT_USER")'))]
	public function deleteRedirectAction($id): Response{
		$redirect = $this->doctrine->getRepository(Redirect::class)->find($id);

		$this->em->remove($redirect);
		$this->em->flush();

		return new Response("Redirect has been deleted.", 204, array("Content-Type" => "application/json"));
	}

	/**
	 * Gets paginated broken redirects.
	 * @return Response Broken redirects, the status code, and the HTTP headers.
	 * @throws ExceptionInterface
	 */
	#[Route('/list', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_REDIRECT_USER")'))]
	public function getRedirectsAction(Request $request): Response{
		[$page, $pageSize] = RequestHelper::pagination($request, 10);
		$itemType = $request->query->get('type') ?? 'broken';

		$redirects = $this->service->getRedirectsPagination($page, $pageSize, $itemType);

		$serialized = $this->serializer->serialize($redirects, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Filter out redirects by from_link and to_link
	 * @param Request $request
	 * @return Response
	 * @throws ExceptionInterface
	 */
	#[Route('/search', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_REDIRECT_USER")'))]
	public function searchRedirectsAction(Request $request): Response{
		$searchTerm = $request->query->get('searchterm');
		$type = $request->query->get('type');

		$redirects = $this->service->getRedirectsByName($searchTerm, $type);

		$serialized = $this->serializer->serialize($redirects, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Gets the redirect by the specified ID.
	 * @param $id // The ID of the redirect.
	 * @return Response The redirect, the status code, and the HTTP headers.
	 * @throws ExceptionInterface
	 */
	#[Route('/{id}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_REDIRECT_USER")'))]
	public function getRedirectAction($id): Response{
		$redirect = $this->doctrine->getRepository(Redirect::class)->findOneBy(["id" => $id]);

		if(!$redirect){
			// Do the following if the redirect is not found.
			return new Response("The redirect you requested was not found.", 404, array("Content-Type" => "application/json"));
		}

		$serialized = $this->serializer->serialize($redirect, "json", ['groups' => 'redir']);

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
	}

	/**
	 * Posts the new redirect from the specified request.
	 * @param Request $request The holder of the information about the new redirect.
	 * @return Response The redirect, the status code, and the HTTP headers.
	 * @throws ExceptionInterface
	 */
	#[Route('/', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_REDIRECT_USER")'))]
	public function postRedirectAction(Request $request): Response{
		$redirect = new Redirect();

		$rawToLink = (string) $request->request->get("toLink");
		$fromLink = $this->urls->normalizeFrom($request->request->get("fromLink"));
		$toLink = $this->urls->normalizeTo($rawToLink);
		if($fromLink === null || $toLink === null){
			return new Response(json_encode("The link is not a valid URL or path."), 422, array("Content-Type" => "application/json"));
		}

		// Set the fields for all redirects.
		$redirect->setFromLink($fromLink);
		$redirect->setToLink($toLink);
		$redirect->setItemType($request->request->get("itemType"));
		$redirect->setVisits(0);

		$errors = $this->service->validate($redirect); // Validate the redirect.

		if(count($errors) > 0){
			// Do the following if there is more than one error.
			$serialized = $this->serializer->serialize($errors, "json", ['groups' => 'redir']);

			return new Response($serialized, 422, array("Content-Type" => "application/json"));
		}

		/* Validation of toLink */

		if(
			$redirect->getItemType() != "invalid redirect of broken link"
			&& $redirect->getItemType() != "invalid redirect of shortened link"
		){
			if($rawToLink != trim($rawToLink)){
				// Check if the toLink has any spaces.
				$message = $redirect->getItemType() == "redirect of broken link"
					? "The actual link should not include any spaces." : "The full link should not include any spaces.";

				$response = new Response($message, 422, array("Content-Type" => "application/json"));

				return $response;
			}

			if($message = $this->brokenLinkMessage($redirect)){
				return new Response($message, 422, array("Content-Type" => "application/json"));
			}
		}

		$this->em->persist($redirect); // Persist the redirect.
		$this->em->flush(); // Commit everything to the database.

		$serialized = $this->serializer->serialize($redirect, "json", ['groups' => 'redir']);

		return new Response($serialized, 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Updates the redirect from the specified request.
	 * @param Request $request The holder of the information about the updated redirect.
	 * @return Response The redirect, the status code, and the HTTP headers.
	 * @throws ExceptionInterface
	 */
	#[Route('/', methods: ['PUT'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_REDIRECT_USER")'))]
	public function putRedirectAction(Request $request): Response{
		$id = $request->request->get("id");
		$fromLink = $request->request->get("fromLink");
		$toLink = $request->request->get("toLink");
		$itemType = $request->request->get("itemType");

		$redirect = $this->doctrine->getRepository(Redirect::class)->find($id);

		if(!$redirect){
			return new Response(json_encode("Redirect not found for id: $id"), 404, array("Content-Type" => "application/json"));
		}

		$rawToLink = (string) $toLink;
		$fromLink = $this->urls->normalizeFrom($fromLink);
		$toLink = $this->urls->normalizeTo($rawToLink);
		if($fromLink === null || $toLink === null){
			return new Response(json_encode("The link is not a valid URL or path."), 422, array("Content-Type" => "application/json"));
		}

		// Set the fields for all redirect objects.
		$redirect->setFromLink($fromLink);
		$redirect->setToLink($toLink);
		$redirect->setItemType($itemType);

		$errors = $this->service->validate($redirect); // Validate the redirect.

		if(count($errors) > 0){
			// Do the following if there is more than one error.
			$serialized = $this->serializer->serialize($errors, "json", ['groups' => 'redir']);

			return new Response($serialized, 422, array("Content-Type" => "application/json"));
		}

		if(
			$redirect->getItemType() != "invalid redirect of broken link"
			&& $redirect->getItemType() != "invalid redirect of shortened link"
			&& $redirect->getItemType() != "expired redirect of broken link"
			&& $redirect->getItemType() != "expired redirect of shortened link"
		){
			// Check if the toLink has any spaces.
			if($rawToLink != trim($rawToLink)){
				$message = $redirect->getItemType() == "redirect of broken link"
					? "The actual link should not include any spaces." : "The full link should not include any spaces.";

				return new Response($message, 422, array("Content-Type" => "application/json"));
			}

			if($message = $this->brokenLinkMessage($redirect)){
				return new Response(json_encode($message), 422, array("Content-Type" => "application/json"));
			}
		}
		else if(
			$redirect->getItemType() == "invalid redirect of broken link"
			|| $redirect->getItemType() == "invalid redirect of shortened link"
		){

			// Some broken redirects may be fixed.
			if($this->linkChecker->check($this->urls->absoluteTo($redirect->getToLink())) === LinkChecker::OK){
				$redirect->setItemType(preg_replace("/invalid /", "", $redirect->getItemType()));
			}
		}

		$this->em->persist($redirect); // Persist the redirect.
		$this->em->flush(); // Commit everything to the database.

		$serialized = $this->serializer->serialize($redirect, "json", ['groups' => 'redir']);

		return new Response($serialized, 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Updates the redirect from the specified request.
	 * @param Request $request The holder of the information about the updated redirect.
	 * @param Profiler|null $profiler
	 * @param DebugDataHolder|null $debugDataHolder
	 * @return Response The redirect, the status code, and the HTTP headers.
	 *
	 * #[Autowire(service: 'doctrine.debug_data_holder')] — Symfony doesn’t type-hint this service by default, so this attribute says “inject that specific service.”
	 */
	#[Route('upload', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_REDIRECT_USER")'))]
	public function postRedirectBulkAction(
		Request          $request,
		?Profiler        $profiler = null,
		#[Autowire(service: 'doctrine.debug_data_holder')]
		?DebugDataHolder $debugDataHolder = null,
	): Response{
		// Profiler and Doctrine's debug query holder retain every SQL (+ backtrace) for
		// the whole request. Disabling/resetting them is required for large CSVs (DEV only; this does nothing in PROD).
		$profiler?->disable();
		$debugDataHolder?->reset();

		$uploadedFile = $request->files->get('csv');
		if(!$uploadedFile){
			return new Response(json_encode("No CSV file provided."), 400, array("Content-Type" => "application/json"));
		}

		$file = file($uploadedFile);
		// Strip UTF-8 BOM that Excel/Google Sheets prepend — it corrupts the first CSV header
		$file[0] = preg_replace('/^\xEF\xBB\xBF/', '', $file[0] ?? '');
		$csvFile = array_map(fn ($line) => str_getcsv($line, ',', '"', '\\'), $file);
		$headers = array_map('trim', array_shift($csvFile) ?? []);

		$added = 0;
		$rejected = 0;

		$rejectedArr = [];

		$csv = array();
		foreach($csvFile as $row){
			if($row === [null] || $row === ['']){
				continue; // blank line
			}
			// A row with the wrong number of columns can't be matched to the headers.
			if(count($row) !== count($headers)){
				++$rejected;
				$rejectedArr[] = $row[0] ?? '';
				continue;
			}
			$csv[] = array_combine($headers, $row);
		}

		if(count($csv) > 0){
			foreach($csv as $redirect){
				$statusCode = $this->_addRedirect($redirect);
				// Clear EM + wipe debug query/backtrace buffer every row.
				$this->em->clear();
				$debugDataHolder?->reset();
				switch($statusCode){
					case 201:
						++$added;
						break;
					case 422:
					default:
						++$rejected;
						$rejectedArr[] = $redirect['from_link'] ?? '';
						break;
				}
			}
		}

		$this->em->clear();
		$debugDataHolder?->reset();

		if($rejected === 0){
			$message = sprintf('%d added.<br>0 rejected or skipped.', $added);
		}
		else{
			$message = sprintf(
				'%d added.<br>%d rejected or skipped (from_link):<br><ul><li>%s</li></ul>',
				$added,
				$rejected,
				implode('</li><li>', $rejectedArr)
			);
		}

		return new Response($message, 201, array("Content-Type" => "application/json"));
	}

	/**
	 * Checks the redirect's target over HTTP.
	 * @return string|null an error message, or null when the target works
	 */
	private function brokenLinkMessage(Redirect $redirect): ?string{
		$result = $this->linkChecker->check($this->urls->absoluteTo($redirect->getToLink()));
		$label = $redirect->getItemType() == "redirect of broken link" ? "The actual link" : "The full link";

		return match ($result) {
			LinkChecker::NOT_FOUND => "$label is not valid.",
			LinkChecker::UNREACHABLE => "$label could not be reached. Check the address and try again.",
			default => null,
		};
	}

	/**
	 * Persist one redirect row from a bulk CSV upload.
	 * Returns an HTTP-style status code (201 created, 422 rejected).
	 * Skips remote link checks — too expensive for bulk imports.
	 */
	private function _addRedirect(array $data): int{
		$from = $data['from_link'] ?? null;
		$type = (string) ($data['item_type'] ?? '');
		$to = $data['to_link'] ?? null;

		$redirect = new Redirect();

		$fromLink = $this->urls->normalizeFrom($from);
		$toLink = $this->urls->normalizeTo($to);
		if($fromLink === null || $toLink === null || $type === ''){
			return 422;
		}

		// Set the fields for all redirects.
		$redirect->setFromLink($fromLink);
		$redirect->setToLink($toLink);
		$redirect->setItemType($type);
		$redirect->setVisits(0);

		$errors = $this->service->validate($redirect); // Validate the redirect.

		if(count($errors) > 0){
			return 422;
		}

		/* Local toLink checks only (no remote link checks in bulk). */

		if(
			$redirect->getItemType() != "invalid redirect of broken link"
			&& $redirect->getItemType() != "invalid redirect of shortened link"
		){
			if($to != trim((string) $to)){
				return 422;
			}
		}

		$this->em->persist($redirect); // Persist the redirect.
		$this->em->flush(); // Commit everything to the database.

		return 201;
	}
}
