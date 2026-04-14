<?php

namespace App\Controller\Api\GradCas;

use App\Entity\GradCas\GradCasCycle;
use App\Entity\GradCas\GradCasLink;
use App\Service\GradCasService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GradCasController extends AbstractController
{
	private GradCasService $service;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;
	private SerializerInterface $serializer;

	public function __construct(GradCasService $service, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
	{
		$this->service = $service;
		$this->doctrine = $doctrine;
		$this->em = $em;
		$this->serializer = $serializer;
	}

	/* ========================= Cycle Endpoints ========================= */

	#[Route('/cycles', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_VIEW")'))]
	public function getCyclesAction(): Response
	{
		$cycles = $this->doctrine->getRepository(GradCasCycle::class)->findAllOrderedByName();

		// Attach link counts
		$linkRepo = $this->doctrine->getRepository(GradCasLink::class);
		$result = [];
		foreach ($cycles as $cycle) {
			$data = json_decode($this->serializer->serialize($cycle, "json", ['groups' => 'gradcas']), true);
			$data['linkCount'] = $linkRepo->countByCycle($cycle->getId());
			$result[] = $data;
		}

		return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/cycles/{id}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_VIEW")'))]
	public function getCycleAction(int $id): Response
	{
		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($id);

		if (!$cycle) {
			return new Response(json_encode("Cycle not found."), 404, ["Content-Type" => "application/json"]);
		}

		$serialized = $this->serializer->serialize($cycle, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/cycles', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN")'))]
	public function postCycleAction(Request $request): Response
	{
		$data = json_decode($request->getContent(), true);

		$cycle = new GradCasCycle();
		$cycle->setCycleName($data['cycleName'] ?? '');

		$errors = $this->service->validate($cycle);
		if (count($errors) > 0) {
			$serialized = $this->serializer->serialize($errors, "json");
			return new Response($serialized, 422, ["Content-Type" => "application/json"]);
		}

		$this->em->persist($cycle);
		$this->em->flush();

		$serialized = $this->serializer->serialize($cycle, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 201, ["Content-Type" => "application/json"]);
	}

	#[Route('/cycles/{id}', methods: ['PUT'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN")'))]
	public function putCycleAction(int $id, Request $request): Response
	{
		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($id);
		if (!$cycle) {
			return new Response(json_encode("Cycle not found."), 404, ["Content-Type" => "application/json"]);
		}

		$data = json_decode($request->getContent(), true);
		$cycle->setCycleName($data['cycleName'] ?? $cycle->getCycleName());

		$errors = $this->service->validate($cycle);
		if (count($errors) > 0) {
			$serialized = $this->serializer->serialize($errors, "json");
			return new Response($serialized, 422, ["Content-Type" => "application/json"]);
		}

		$this->em->flush();

		$serialized = $this->serializer->serialize($cycle, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/cycles/{id}/current', methods: ['PUT'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN")'))]
	public function setCycleCurrentAction(int $id): Response
	{
		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($id);
		if (!$cycle) {
			return new Response(json_encode("Cycle not found."), 404, ["Content-Type" => "application/json"]);
		}

		$this->doctrine->getRepository(GradCasCycle::class)->setCurrentCycle($id);

		// Refresh the entity to get updated state
		$this->em->refresh($cycle);

		$serialized = $this->serializer->serialize($cycle, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/cycles/{id}', methods: ['DELETE'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN")'))]
	public function deleteCycleAction(int $id): Response
	{
		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($id);
		if (!$cycle) {
			return new Response(json_encode("Cycle not found."), 404, ["Content-Type" => "application/json"]);
		}

		$this->em->remove($cycle);
		$this->em->flush();

		return new Response(null, 204);
	}

	/* ========================= Link Endpoints ========================= */

	#[Route('/links/{cycleId}', methods: ['GET'], requirements: ['cycleId' => '\d+'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_VIEW")'))]
	public function getLinksAction(int $cycleId, Request $request): Response
	{
		$page = $request->query->get('page') ?? 1;
		$pageSize = $request->query->get('limit') ?? 20;

		$links = $this->service->getLinksPagination($cycleId, $page, $pageSize);

		$serialized = $this->serializer->serialize($links, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/links/{cycleId}/search', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_VIEW")'))]
	public function searchLinksAction(int $cycleId, Request $request): Response
	{
		$searchTerm = $request->query->get('searchterm');
		if (!$searchTerm) {
			return new Response(json_encode([]), 200, ["Content-Type" => "application/json"]);
		}

		$links = $this->service->getLinksByName($searchTerm, $cycleId);

		$serialized = $this->serializer->serialize($links, "json");
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/link/{id}', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_VIEW")'))]
	public function getLinkAction(int $id): Response
	{
		$link = $this->doctrine->getRepository(GradCasLink::class)->find($id);

		if (!$link) {
			return new Response("Link not found.", 404, ["Content-Type" => "application/json"]);
		}

		$serialized = $this->serializer->serialize($link, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/links', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_EDIT")'))]
	public function postLinkAction(Request $request): Response
	{
		$data = json_decode($request->getContent(), true);

		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($data['cycleId'] ?? 0);
		if (!$cycle) {
			return new Response(json_encode("Cycle not found."), 404, ["Content-Type" => "application/json"]);
		}

		$degreeName = $data['degreeName'] ?? '';

		/** @var \App\Repository\GradCas\GradCasLinkRepository $linkRepo */
		$linkRepo = $this->doctrine->getRepository(GradCasLink::class);
		$existing = $linkRepo->findByDegreeNameAndCycle($degreeName, $cycle->getId());
		if ($existing) {
			return new Response(json_encode([
				'error' => 'duplicate',
				'message' => 'A link with this degree name already exists in this cycle.',
				'existingId' => $existing->getId()
			]), 409, ["Content-Type" => "application/json"]);
		}

		$link = new GradCasLink();
		$link->setCycle($cycle);
		$link->setDegreeName($degreeName);
		$link->setLink($data['link'] ?? '');
		$link->setProgramId($data['programId'] ?? null);

		$errors = $this->service->validate($link);
		if (count($errors) > 0) {
			$serialized = $this->serializer->serialize($errors, "json");
			return new Response($serialized, 422, ["Content-Type" => "application/json"]);
		}

		$this->em->persist($link);
		$this->em->flush();

		$serialized = $this->serializer->serialize($link, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 201, ["Content-Type" => "application/json"]);
	}

	#[Route('/links/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_EDIT")'))]
	public function putLinkAction(int $id, Request $request): Response
	{
		$link = $this->doctrine->getRepository(GradCasLink::class)->find($id);
		if (!$link) {
			return new Response(json_encode("Link not found."), 404, ["Content-Type" => "application/json"]);
		}

		$data = json_decode($request->getContent(), true);
		$degreeName = $data['degreeName'] ?? $link->getDegreeName();

		/** @var \App\Repository\GradCas\GradCasLinkRepository $linkRepo */
		$linkRepo = $this->doctrine->getRepository(GradCasLink::class);
		$existing = $linkRepo->findByDegreeNameAndCycle($degreeName, $link->getCycle()->getId(), $id);
		if ($existing) {
			return new Response(json_encode([
				'error' => 'duplicate',
				'message' => 'A link with this degree name already exists in this cycle.',
				'existingId' => $existing->getId()
			]), 409, ["Content-Type" => "application/json"]);
		}

		$link->setDegreeName($degreeName);
		$link->setLink($data['link'] ?? $link->getLink());
		$link->setProgramId($data['programId'] ?? $link->getProgramId());

		$errors = $this->service->validate($link);
		if (count($errors) > 0) {
			$serialized = $this->serializer->serialize($errors, "json");
			return new Response($serialized, 422, ["Content-Type" => "application/json"]);
		}

		$this->em->flush();

		$serialized = $this->serializer->serialize($link, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/links/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_EDIT")'))]
	public function deleteLinkAction(int $id): Response
	{
		$link = $this->doctrine->getRepository(GradCasLink::class)->find($id);
		if (!$link) {
			return new Response(json_encode("Link not found."), 404, ["Content-Type" => "application/json"]);
		}

		$this->em->remove($link);
		$this->em->flush();

		return new Response(null, 204);
	}

	#[Route('/links/upload', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN")'))]
	public function postLinkBulkAction(Request $request): Response
	{
		$uploadedFile = $request->files->get('csv');
		if (!$uploadedFile) {
			return new Response(json_encode("No CSV file provided."), 400, ["Content-Type" => "application/json"]);
		}

		$file = file($uploadedFile);
		$csvFile = array_map('str_getcsv', $file);
		$headers = array_shift($csvFile);

		$csv = array();
		foreach ($csvFile as $row) {
			if (count($row) === count($headers)) {
				$csv[] = array_combine($headers, $row);
			}
		}

		$cycleId = $request->request->get('cycleId');
		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($cycleId);
		if (!$cycle) {
			return new Response(json_encode("Cycle not found."), 404, ["Content-Type" => "application/json"]);
		}

		/** @var EntityManagerInterface $programsEm */
		$programsEm = $this->doctrine->getManager('programs');
		$programsConn = $programsEm->getConnection();
		$programRows = $programsConn->executeQuery(
			'SELECT id, full_name FROM programs.program_programs'
		)->fetchAllAssociative();

		// Load all rows into an array for quick lookup by full_name
		$programLookup = [];
		foreach ($programRows as $row) {
			$programLookup[mb_strtolower(trim($row['full_name']))] = (int) $row['id'];
		}

		/** @var \App\Repository\GradCas\GradCasLinkRepository $linkRepo */
		$linkRepo = $this->doctrine->getRepository(GradCasLink::class);
		$existingLinks = $linkRepo->findByCycle($cycleId);
		$existingNames = [];
		foreach ($existingLinks as $existingLink) {
			$existingNames[mb_strtolower(trim($existingLink->getDegreeName()))] = true;
		}

		$added = 0;
		$rejected = 0;
		$rejectedArr = [];
		$seenInCsv = [];

		foreach ($csv as $row) {
			$degreeName = trim($row['degree_name'] ?? '');
			$linkUrl = trim($row['link'] ?? '');

			if ($degreeName === '' && $linkUrl === '') {
				continue;
			}

			$nameKey = mb_strtolower($degreeName);

			if (isset($existingNames[$nameKey]) || isset($seenInCsv[$nameKey])) {
				$rejected++;
				$rejectedArr[] = $degreeName . ' (duplicate)';
				continue;
			}

			$programId = $programLookup[$nameKey] ?? null;

			$link = new GradCasLink();
			$link->setCycle($cycle);
			$link->setDegreeName($degreeName);
			$link->setLink($linkUrl);
			$link->setProgramId($programId);

			$errors = $this->service->validate($link);
			if (count($errors) > 0) {
				$rejected++;
				$rejectedArr[] = $degreeName ?: '(empty)';
			} else {
				$this->em->persist($link);
				$added++;
				$seenInCsv[$nameKey] = true;
			}
		}

		$this->em->flush();

		return new Response(
			sprintf('%d added. %d rejected: %s', $added, $rejected, implode(', ', $rejectedArr)),
			201,
			["Content-Type" => "application/json"]
		);
	}

	/* ========================= Programs Endpoint ========================= */

	#[Route('/programs', methods: ['GET'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_GRADCAS_ADMIN") or is_granted("ROLE_GRADCAS_VIEW")'))]
	public function getProgramsAction(): Response
	{
		$programs = $this->service->getGraduatePrograms();
		return new Response(json_encode($programs), 200, ["Content-Type" => "application/json"]);
	}
}
