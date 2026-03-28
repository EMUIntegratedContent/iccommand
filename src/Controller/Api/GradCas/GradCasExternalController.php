<?php

namespace App\Controller\Api\GradCas;

use App\Entity\GradCas\GradCasCycle;
use App\Entity\GradCas\GradCasLink;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GradCasExternalController extends AbstractController
{
	private ManagerRegistry $doctrine;
	private SerializerInterface $serializer;

	public function __construct(ManagerRegistry $doctrine, SerializerInterface $serializer)
	{
		$this->doctrine = $doctrine;
		$this->serializer = $serializer;
	}

	#[Route('/cycles', methods: ['GET'])]
	public function getCyclesAction(): Response
	{
		$cycles = $this->doctrine->getRepository(GradCasCycle::class)->findAllOrderedByName();

		$serialized = $this->serializer->serialize($cycles, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/links', methods: ['GET'])]
	public function getLinksAction(Request $request): Response
	{
		$cycleId = $request->query->get('cycle');

		if ($cycleId) {
			$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($cycleId);
		} else {
			// Default to the current cycle
			$cycle = $this->doctrine->getRepository(GradCasCycle::class)->findCurrentCycle();
		}

		if (!$cycle) {
			return new Response(json_encode("No cycle found."), 404, ["Content-Type" => "application/json"]);
		}

		$links = $this->doctrine->getRepository(GradCasLink::class)->findByCycle($cycle->getId());

		$serialized = $this->serializer->serialize($links, "json", ['groups' => 'gradcas']);
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}
}
