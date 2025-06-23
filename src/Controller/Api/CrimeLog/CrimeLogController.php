<?php

namespace App\Controller\Api\CrimeLog;

use App\Entity\CrimeLog\CrimeLog;
use App\Service\CrimeLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
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
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;
	private SerializerInterface $serializer;

	/**
	 * The constructor of the CrimeLogController.
	 * @param CrimeLogService $service The service container of this controller.
	 */
	public function __construct(CrimeLogService $service, LoggerInterface $logger, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
	{
		$this->service = $service;
		$this->logger = $logger;
		$this->doctrine = $doctrine;
		$this->em = $em;
		$this->serializer = $serializer;
	}

	#[Route('/logs', methods: ['GET'])]
	public function getCrimeLog(Request $request): Response
	{
		/*$page = $request->query->get('page') ?? 1;
		$pageSize = $request->query->get('limit') ?? 10;
		$itemType = $request->query->get('type') ?? 'broken';

		$redirects = $this->service->getRedirectsPagination($page, $pageSize, $itemType);*/

		$serialized = $this->serializer->serialize($request, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));
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

		$csv    = array();
		foreach ($csvFile as $row) {
			$csv[] = array_combine($headers, $row);
		}

		$added = 0;
		$rejected = 0;

		$rejectedArr = [];

		if (count($csv) > 0) {
			foreach ($csv as $crimelog) {
				$newCrimeLog = $this->_addCrimeLog($crimelog);
				switch ($newCrimeLog->getStatusCode()) {
					case 201:
						++$added;
						break;
					case 422:
					default:
						++$rejected;
						$rejectedArr[] = $crimelog['crnnumber'];
						break;
				}
			}
		}

		return new Response(sprintf('%d added.<br>%d rejected or skipped (crnnumber):<br><ul><li>%s</li></ul>', $added, $rejected, implode('</li><li>', $rejectedArr)), 201, array("Content-Type" => "application/json"));
	}

	private function _addCrimeLog($data): Response
	{
		$crnnumber = $data['crnnumber'];
		$crime = $data['crime'];
		$crimedesc = $data['crimedesc'];
		$att = $data['att'];
		$arson = $data['arson'];
		$reptdate = $data['reptdate']; //date_parse("2025-06-20");
		$repttime = $data['repttime'];
		$occurdate1 = $data['occurdate1'];
		$occurdate2 = $data['occurdate2'];
		$status = $data['status'];
		$closed = $data['closed'];
		$lastupdate = $data['lastupdate'];
		$location = $data['location'];
		$subject = $data['subject'];

		$crimelog = new CrimeLog();

		// Set the fields for all crimelogs.
		$crimelog->setIncidentNumber($crnnumber);
		$crimelog->setCrime($crime);
		$crimelog->setCrimeDescription($crimedesc);
		$crimelog->setAttn($att);
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

		//$errors = $this->service->validate($crimelog); // Validate the crimelog. GMC
		$errors = []; //gmc

		if (count($errors) > 0) {
			// Do the following if there is more than one error.
			$serialized = $this->serializer->serialize($errors, "json", ['groups' => 'redir']);

			return new Response($serialized, 422, array("Content-Type" => "application/json"));
		}

		$this->em->persist($crimelog); // Persist the crimelog.
		$this->em->flush(); // Commit everything to the database.

		$serialized = $this->serializer->serialize($crimelog, "json", ['groups' => 'crime']);

		return new Response($serialized, 201, array("Content-Type" => "application/json"));
	}
}
