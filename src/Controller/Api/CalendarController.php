<?php

namespace App\Controller\Api;

use App\Entity\MultimediaRequest\PhotoHeadshotDate;
use App\Service\MultimediaRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CalendarController extends AbstractFOSRestController
{
	private SerializerInterface $serializer;
	private MultimediaRequestService $service;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;

	public function __construct(MultimediaRequestService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em)
	{
		$this->service = $service;
		$this->serializer = $serializer;
		$this->doctrine = $doctrine;
		$this->em = $em;
	}

	#[Route('/{entityType}/{year}/{month}', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function getCalendarMonthEventsAction($entityType, $year, $month): Response
	{
		// Figure out which entity we're getting events for
		$entityClass = null;
		switch ($entityType){
			case 'headshots':
				$entityClass = PhotoHeadshotDate::class;
				break;
			default:
				$response = new Response('The entity class you provided is not valid for this endpoint.', 400);
				return $response;
		}

		// A few dates to get us started
		$monthStart = Carbon::create($year, $month)->startOfMonth();
		$monthEnd =  Carbon::create($year, $month)->endOfMonth();
		$monthStartDay =  $monthStart->firstOfMonth()->dayOfWeek;
		$monthNumDays = $monthStart->daysInMonth;

		// Get all the days in this month that have events
		$daysOfMonthWithEvents = $this->doctrine->getRepository($entityClass)->findByGroupped($monthStart, $monthEnd);
		$uniqueByDay = array(); // initialize the array that will store the day of month value
		foreach($daysOfMonthWithEvents as $dayOfMonthWithEvent){
			$uniqueByDay[] = $dayOfMonthWithEvent['dateOfShoot']->format('d'); // DateTime::format()
		}

		$calDaysArray = array(); // initialize calendar days array for the month
		$dayCounter = 0;
		// These are the days of the week that come before the start of the month
		while ($dayCounter < $monthStartDay) {
			// On the public form, the 'x' will tell our code that this day does NOT belong to the current month
			$dayObject = array('day' => 'x'.$dayCounter , 'hasevents'=> 'no-events');
			$calDaysArray[] = $dayObject;
			$dayCounter++;
		}
		// Add the days of the month to the $calDaysArray with an indicator of whether that day has any events
		for ($day = 1; $day <= $monthNumDays; $day++){
			$hasevent = in_array($day, $uniqueByDay) ? 'yes-events' : 'no-events';
			$dayObject = array('day' => $day, 'hasevents'=> $hasevent);
			$calDaysArray[] = $dayObject;

			$dayCounter++;
		}

		$serialized = $this->serializer->serialize([
			'uniqueByDay'         => $uniqueByDay,
			'calDaysArray'        => $calDaysArray,
		], 'json');

		return new Response($serialized, 200, array('Content-Type' => 'application/json'));
	}
}
