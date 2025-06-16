<?php

namespace App\Controller\Api\CrimeLog;

//use Doctrine\Persistence\CrimeLog;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Find a redirect URL passed from an external source
 * @param Request $request
 * @return Response
 */
class CrimeLogController extends AbstractFOSRestController
{
	//public function index(): Response
	#[Route('/crimelog', methods: ['GET'])]
	public function getCrimeLog(Request $request): Response
	{
		/*$page = $request->query->get('page') ?? 1;
		$pageSize = $request->query->get('limit') ?? 10;
		$itemType = $request->query->get('type') ?? 'broken';

		$redirects = $this->service->getRedirectsPagination($page, $pageSize, $itemType);

		$serialized = $this->serializer->serialize($redirects, "json");

		return new Response($serialized, 200, array("Content-Type" => "application/json"));*/
	}
}
