<?php

namespace App\Controller\Api\CrimeLog;

use App\Entity\CrimeLog\CrimeLog;
use App\Service\CrimeLogService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;


if (!ini_get('display_errors')) {
	ini_set('display_errors', '1');
}
error_reporting(E_ALL);

/**
 * API CrimeLog Controller
 * This controller manages the crimelog with the actions of adding.
 */
class CrimeLogController extends AbstractFOSRestController
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
	 */
	#[Route('upload', methods: ['POST'])]
	public function postCrimeLogBulkAction(Request $request): Response
	{
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

			foreach ($csv as $crimelog) {
				$newCrimeLog = $this->_addCrimeLog($crimelog);
				if ($newCrimeLog['success'] === false) {
					$rejected++;
					$rejectedArr[] = $crimelog['Incident Number'];
				} else {
					$added++;
				}
			}
			$this->em->flush(); // Commit everything to the database.
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
		$this->logger->info('Processing crime log entry', [
			'incident_number' => $crnnumber,
			'data_keys' => array_keys($data)
		]);

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
		$this->logger->info('Crime log validation', [
			'errors' => $errors
		]);

		if (count($errors) > 0) {
			$errorMessages = [];
			foreach ($errors as $error) {
				$errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
			}

			$this->logger->warning('Crime log validation failed', [
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
			$this->em->persist($crimelog); // Persist the crimelog.
			// $this->em->flush(); // Moved outside of this method to the bulk action because it was causing memory issues.

			$this->logger->info('Crime log successfully added', [
				'incident_number' => $crnnumber,
				'crime_type' => $crime,
				'location' => $location
			]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to persist crime log', [
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

		$serialized = $this->serializer->serialize($crimelog, "json", ['groups' => 'crimelog']);

		return [
			'success' => true,
			'code' => 201,
			'data' => $serialized
		];
	}
}
