<?php

namespace App\Controller\Api\CrimeLog;

use App\Entity\CrimeLog\CrimeLog;
use App\Entity\CrimeLog\FireLog;
use App\Service\CrimeLogService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API CrimeLog Controller
 * This controller manages the crimelog with the actions of adding.
 */
class CrimeLogController extends AbstractController
{
	private CrimeLogService $service;
	private LoggerInterface $logger;
	private ObjectManager $em;
	private SerializerInterface $serializer;

	/**
	 * The constructor of the CrimeLogController.
	 * @param CrimeLogService $service The service container of this controller.
	 */
	public function __construct(
		CrimeLogService $service,
		LoggerInterface $logger,
		ManagerRegistry $doctrine,
		SerializerInterface $serializer
	) {
		$this->service = $service;
		$this->logger = $logger;
		$this->em = $doctrine->getManager('dps');
		$this->serializer = $serializer;
	}

	/**
	 * Updates the crimelog from the specified request.
	 * @param Request $request The holder of the information about the updated crimelog.
	 * @return Response The crimelog, the status code, and the HTTP headers.
	 *
	 * #[Autowire(service: 'doctrine.debug_data_holder')] — Symfony doesn’t type-hint this service by default, so this attribute says “inject that specific service.”
	 * @var DebugDataHolder $debugDataHolder = null — optional. In dev you get the holder and can $debugDataHolder->reset() after each batch. In prod the service often isn’t wired the same way, so $debugDataHolder is null and the ?->reset() calls no-op.
	 * Without that, every SQL query (plus backtrace) would pile up in memory for the whole upload request in dev.
	 */
	#[Route('upload', methods: ['POST'])]
	public function postCrimeLogBulkAction(
		Request $request,
		?Profiler $profiler = null,
		#[Autowire(service: 'doctrine.debug_data_holder')]
		?DebugDataHolder $debugDataHolder = null,
	): Response {
		// Profiler and Doctrine's debug query holder retain every SQL (+ backtrace) for
		// the whole request. Disabling/resetting them is required for large CSVs (DEV only; this does nothing in PROD).
		$profiler?->disable();
		$debugDataHolder?->reset();

		$file = file($request->files->get('csv'));

		$csvFile = array_map('str_getcsv', $file);
		$headers = array_shift($csvFile);

		$csv = array();
		foreach ($csvFile as $row) {
			$csv[] = array_combine($headers, $row);
		}

		$added = 0;
		$rejected = 0;

		$rejectedArr = [];

		if (count($csv) > 0) {
			// Truncate the crimelog table before adding new entries.
			$this->service->truncateCrimeLogTable();

			$rowsSinceLastClear = 0;
			foreach ($csv as $crimelog) {
				$newCrimeLog = $this->_addCrimeLog($crimelog);
				if ($newCrimeLog['success'] === false) {
					$rejected++;
					$rejectedArr[] = $crimelog['Incident Number'];
				} else {
					$added++;
				}
				// Only certain codes are considered fire logs. They will be filtered in the method.
				$this->_addFireLog($crimelog);

				$rowsSinceLastClear++;
				if ($rowsSinceLastClear >= 50) {
					// Clear EM + wipe debug query/backtrace buffer every 50 rows.
					$this->em->flush();
					$this->em->clear();
					$debugDataHolder?->reset();
					$rowsSinceLastClear = 0;
				}
			}
			$this->em->flush();
			$this->em->clear();
			$debugDataHolder?->reset();
		}

		if ($rejected === 0) {
			return new Response(sprintf('%d added.<br>0 rejected or skipped.', $added), 201, array("Content-Type" => "application/json"));
		}
		return new Response(sprintf('%d added.<br>%d rejected or skipped (Incident Number):<br><ul><li>%s</li></ul>', $added, $rejected, implode('</li><li>', $rejectedArr)), 201, array("Content-Type" => "application/json"));
	}

	private function _addCrimeLog($data): array
	{
		$crnnumber = $data['Incident Number'];

		// Log the start of processing
		// $this->logger->info('Processing crime log entry', [
		// 	'incident_number' => $crnnumber,
		// 	'data_keys' => array_keys($data)
		// ]);

		$crime = $data['Crime'];
		$crimedesc = $data['Crime Description'];
		$att = $data['Att'];
		$arson = $data['Arson'];
		$reptdate = $data['Report Date'] ? date('Y-m-d', strtotime($data['Report Date'])) : null;
		$repttime = $data['Report Time'];
		$occurdate1 = $data['Occur From'];
		$occurdate2 = $data['Occur To'];
		$status = $data['Status'];
		$closed = $data['Closed'];
		$lastupdate = $data['Last Approval'];
		$location = $data['Location'];
		$subject = $data['Subject'];

		$crimelog = new CrimeLog();

		// Set the fields for all crimelogs.
		$crimelog->setIncidentNumber($crnnumber);
		$crimelog->setCrime($crime);
		$crimelog->setCrimeDescription($crimedesc);
		$crimelog->setAtt($att);
		$crimelog->setArson($arson);
		$crimelog->setReportDate($reptdate);
		$crimelog->setReportTime($repttime);
		$crimelog->setOccurFrom($occurdate1);
		$crimelog->setOccurTo($occurdate2);
		$crimelog->setStatus($status);
		$crimelog->setClosed($closed);
		$crimelog->setLastApproval($lastupdate);
		$crimelog->setLocation($location);
		$crimelog->setSubject($subject);

		$errors = $this->service->validate($crimelog);
		// $this->logger->info('Crime log validation', [
		// 	'errors' => $errors
		// ]);

		if (count($errors) > 0) {
			$errorMessages = [];
			foreach ($errors as $error) {
				$errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
			}

			// $this->logger->warning('Crime log validation failed', [
			// 	'incident_number' => $crnnumber,
			// 	'errors' => $errorMessages
			// ]);

			$serialized = $this->serializer->serialize($errors, "json", ['groups' => 'crimelog']);
			return [
				'success' => false,
				'code' => 422,
				'data' => $serialized
			];
		}

		try {
			$this->em->persist($crimelog); // Persist the crimelog.
			// $this->em->flush(); // Moved outside of this method to the bulk action because it was causing memory issues.

			// $this->logger->info('Crime log successfully added', [
			// 	'incident_number' => $crnnumber,
			// 	'crime_type' => $crime,
			// 	'location' => $location
			// ]);
		} catch (\Exception $e) {
			// $this->logger->error('Failed to persist crime log', [
			// 	'incident_number' => $crnnumber,
			// 	'error' => $e->getMessage(),
			// 	'trace' => $e->getTraceAsString()
			// ]);

			return [
				'success' => false,
				'code' => 500,
				'data' => 'Database error occurred'
			];
		}

		$serialized = $this->serializer->serialize($crimelog, "json", ['groups' => 'crimelog']);

		return [
			'success' => true,
			'code' => 201,
			'data' => $serialized
		];
	}

	// Only certain crime codes are considered fire logs.
	private function _addFireLog($data): array
	{
		$fireCodes = ['L5170', '2005', '2009', '2072', '2073', '2099'];
		$crime = $data['Crime'];
		if (!in_array($crime, $fireCodes)) {
			// Not a fire log, skip processing.
			return [
				'success' => false,
				'code' => 204,
				'data' => 'Not eligible for fire log.'
			];
		}
		$crnnumber = $data['Incident Number'];

		// Log the start of processing
		// $this->logger->info('Processing fire log entry', [
		// 	'incident_number' => $crnnumber,
		// 	'data_keys' => array_keys($data)
		// ]);

		$crimedesc = $data['Crime Description'];
		$att = $data['Att'];
		$reptdate = $data['Report Date'] ? date('Y-m-d', strtotime($data['Report Date'])) : null;
		$repttime = $data['Report Time'];
		$occurdate1 = $data['Occur From'];
		$occurdate2 = $data['Occur To'];
		$status = $data['Status'];
		$closed = $data['Closed'];
		$lastupdate = $data['Last Approval'];
		$location = $data['Location'];
		$subject = $data['Subject'];

		// Find an existing fire log by incident number to avoid duplicates.
		$fireLog = $this->service->findFireLogByIncidentNumber($crnnumber);
		// Log the fire log lookup result.
		$this->logger->info('Fire log lookup result', [
			'incident_number' => $fireLog ? $fireLog['crnnumber'] : null,
			'found' => $fireLog !== null,
			'id' => $fireLog ? $fireLog['id'] : null
		]);

		// Manually delete the fire log if it exists. This is necessary because the entity is not being deleted properly when using $this->em->remove($fireLog); No idea why.
		if ($fireLog) {
			$this->service->deleteFireLogById($fireLog['id']);
		}

		// (Re)reate the fire log.
		$fireLog = new FireLog();
		$fireLog->setIncidentNumber($crnnumber);

		// Set the fields for all crimelogs.
		$fireLog->setCrime($crime);
		$fireLog->setCrimeDescription($crimedesc);
		$fireLog->setAtt($att);
		$fireLog->setReportDate($reptdate);
		$fireLog->setReportTime($repttime);
		$fireLog->setOccurFrom($occurdate1);
		$fireLog->setOccurTo($occurdate2);
		$fireLog->setStatus($status);
		$fireLog->setClosed($closed);
		$fireLog->setLastApproval($lastupdate);
		$fireLog->setLocation($location);
		$fireLog->setSubject($subject);

		$errors = $this->service->validateFireLog($fireLog);
		$this->logger->info('Fire log validation', [
			'errors' => $errors
		]);

		if (count($errors) > 0) {
			$errorMessages = [];
			foreach ($errors as $error) {
				$errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
			}

			$this->logger->warning('Fire log validation failed', [
				'incident_number' => $crnnumber,
				'errors' => $errorMessages
			]);

			$serialized = $this->serializer->serialize($errors, "json", ['groups' => 'crimelog']);
			return [
				'success' => false,
				'code' => 422,
				'data' => $serialized
			];
		}

		try {
			$this->em->persist($fireLog); // Persist the firelog.
			// $this->em->flush(); // Moved outside of this method to the bulk action because it was causing memory issues.

			$this->logger->info('Fire log successfully added', [
				'incident_number' => $crnnumber,
				'crime_type' => $crime,
				'location' => $location
			]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to persist fire log', [
				'incident_number' => $crnnumber,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return [
				'success' => false,
				'code' => 500,
				'data' => 'Database error occurred'
			];
		}

		$serialized = $this->serializer->serialize($fireLog, "json", ['groups' => 'crimelog']);

		return [
			'success' => true,
			'code' => 201,
			'data' => $serialized
		];
	}
}
