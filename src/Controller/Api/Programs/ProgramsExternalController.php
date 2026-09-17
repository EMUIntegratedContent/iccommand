<?php

namespace App\Controller\Api\Programs;

use App\Entity\Programs\Programs;
use App\Service\ProgramsService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProgramsExternalController extends AbstractController
{
	private ManagerRegistry $doctrine;
	private SerializerInterface $serializer;
	private ProgramsService $programsService;

	public function __construct(ManagerRegistry $doctrine, SerializerInterface $serializer, ProgramsService $programsService)
	{
		$this->doctrine = $doctrine;
		$this->serializer = $serializer;
		$this->programsService = $programsService;
	}

	#[Route('/programs', methods: ['GET'])]
	public function getProgramsAction(): Response
	{
		$programs = $this->doctrine->getRepository(Programs::class)->findAll();

		$serialized = $this->serializer->serialize($programs, "json");
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	/**
	 * Public faceted search for the Modern Campus Degrees & Programs page.
	 * Full path: GET /api/external/programs/search
	 * Query params: program, level[], degree[], mode[], department, college, pathway, match, sort, page.
	 * Returns JSON: { programs: [...], count, pages, offset, end, areasOfStudy: {...} }.
	 */
	#[Route('/search', methods: ['GET'])]
	public function getDegreeSearchAction(Request $request): Response
	{
		$result = $this->programsService->searchDegreePrograms($request->query->all());

		return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
	}
}
