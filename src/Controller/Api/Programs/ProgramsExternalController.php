<?php

namespace App\Controller\Api\Programs;

use App\Entity\Programs\Programs;
use App\Service\ProgramsService;
use App\Service\IcService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProgramsExternalController extends AbstractController
{
	private ManagerRegistry $doctrine;
	private SerializerInterface $serializer;
	private ProgramsService $programsService;

	public function __construct(ManagerRegistry $doctrine, SerializerInterface $serializer, ProgramsService $programsService, private IcService $icService)
	{
		$this->doctrine = $doctrine;
		$this->serializer = $serializer;
		$this->programsService = $programsService;
	}

	/**
	 * All active programs. Each carries colleges and departments arrays (names) from the
	 * link tables, since a program can belong to several of each, and icContactInfo: its
	 * contact's public details, or null.
	 */
	#[Route('/programs', methods: ['GET'])]
	public function getProgramsAction(NormalizerInterface $normalizer): Response
	{
		$programs = $this->doctrine->getRepository(Programs::class)->findBy(['is_active' => true]);
		$linked = $this->programsService->getLinkedNamesByProgram();
		$contactInfo = $this->icService->getIcContactInfoById();

		$data = [];
		foreach ($programs as $program) {
			$row = $normalizer->normalize($program, "json");
			$row['colleges'] = $linked['colleges'][$program->getId()] ?? [];
			$row['departments'] = $linked['departments'][$program->getId()] ?? [];
			$row['icContactInfo'] = $contactInfo[$program->getContactId() ?? 0] ?? null;
			$data[] = $row;
		}

		return new Response(json_encode($data), 200, ["Content-Type" => "application/json"]);
	}

	/**
	 * Public faceted search for the Modern Campus Degrees & Programs page.
	 * Full path: GET /api/external/programs/search
	 * Query params: program, level[], degree[], mode[], department, college, pathway, match, sort, page.
	 * Returns JSON: { programs: [...], count, pages, offset, end, areasOfStudy: {...} }.
	 * Each program carries icContactInfo: its contact's public details, or null.
	 */
	#[Route('/search', methods: ['GET'])]
	public function getDegreeSearchAction(Request $request): Response
	{
		$result = $this->programsService->searchDegreePrograms($request->query->all());
		$result['programs'] = $this->icService->addIcContactInfo($result['programs']);

		return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
	}
}
