<?php

namespace App\Controller\Api\CrimeLog;

use App\Service\CrimeLogImporter;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API CrimeLog Controller
 * Handles the Daily Crime Log CSV upload.
 */
class CrimeLogController extends AbstractController
{
	public function __construct(private LoggerInterface $logger)
	{
	}

	/**
	 * Replaces the Daily Crime Log with the uploaded CSV. If any row is invalid, nothing changes
	 * and the response lists the rows to fix (422).
	 * @param Request $request The holder of the uploaded CSV.
	 * @param CrimeLogImporter $importer
	 * @param Profiler|null $profiler
	 * @param DebugDataHolder|null $debugDataHolder
	 * @return Response The import summary, the status code, and the HTTP headers.
	 *
	 * #[Autowire(service: 'doctrine.debug_data_holder')] — Symfony doesn’t type-hint this service by default, so this attribute says “inject that specific service.”
	 */
	#[Route('upload', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CRIMELOG_USER")'))]
	public function postCrimeLogBulkAction(
		Request $request,
		CrimeLogImporter $importer,
		?Profiler $profiler = null,
		#[Autowire(service: 'doctrine.debug_data_holder')]
		?DebugDataHolder $debugDataHolder = null,
	): Response {
		// Profiler and Doctrine's debug query holder retain every SQL (+ backtrace) for
		// the whole request. Disabling/resetting them is required for large CSVs (DEV only; this does nothing in PROD).
		$profiler?->disable();
		$debugDataHolder?->reset();

		$file = $request->files->get('csv');
		if (!$file instanceof UploadedFile || !$file->isValid()) {
			return new JsonResponse(['message' => 'No CSV file was uploaded.', 'errors' => []], 400);
		}

		$result = $importer->parse($file->getPathname());

		if ($result['errors']) {
			// Nothing was written. List each bad row so it can be fixed and re-uploaded.
			$items = array_map(fn (array $e) => sprintf(
				'<li>Row %d%s: %s</li>',
				$e['row'],
				$e['incidentNumber'] !== null ? ' (' . htmlspecialchars($e['incidentNumber'], ENT_QUOTES) . ')' : '',
				htmlspecialchars(implode(' ', $e['errors']), ENT_QUOTES)
			), $result['errors']);
			$message = sprintf(
				'The crime log was not changed. %d row(s) need fixing:<br><ul>%s</ul>',
				count($result['errors']),
				implode('', $items)
			);

			return new JsonResponse(['message' => $message, 'errors' => $result['errors']], 422);
		}

		if (!$result['crimeLogs']) {
			return new JsonResponse(['message' => 'The CSV has no rows. The crime log was not changed.', 'errors' => []], 422);
		}

		try {
			$importer->replace($result['crimeLogs'], $result['fireLogs'], fn () => $debugDataHolder?->reset());
		} catch (\Throwable $e) {
			$this->logger->error('Crime log import failed and was rolled back', ['exception' => $e]);

			return new JsonResponse(['message' => 'The import failed and was rolled back. The crime log was not changed.', 'errors' => []], 500);
		}

		return new Response(
			sprintf('%d added.<br>0 rejected or skipped.<br>%d fire log entries updated.', count($result['crimeLogs']), count($result['fireLogs'])),
			201,
			["Content-Type" => "application/json"]
		);
	}
}
