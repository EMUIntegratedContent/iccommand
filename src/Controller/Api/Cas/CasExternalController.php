<?php

namespace App\Controller\Api\Cas;

use App\Entity\Cas\CasCycle;
use App\Entity\Cas\CasLink;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CasExternalController extends AbstractController
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
		$cycles = $this->doctrine->getRepository(CasCycle::class)->findPublicCycles();

		// Just return the cycleName and id for the external API
		$result = array_map(fn($cycle) => [
			'cycleName' => $cycle->getCycleName(),
			'id' => $cycle->getId(),
		], $cycles);

		$serialized = $this->serializer->serialize($result, "json");
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/links/{cycleId}', methods: ['GET'])]
	public function getLinksAction(int $cycleId): Response
	{
		$cycle = $this->doctrine->getRepository(CasCycle::class)->find($cycleId);

		if (!$cycle || !$cycle->isPublic()) {
			return new Response(json_encode("Cycle not found."), 404, ["Content-Type" => "application/json"]);
		}

		/** @var \App\Repository\Cas\CasLinkRepository $linkRepo */
		$linkRepo = $this->doctrine->getRepository(CasLink::class);
		$links = $linkRepo->findByCycle($cycle->getId());

		// Just return the degreeName and link for the external API
		$result = array_map(fn($link) => [
			'degreeName' => $link->getDegreeName(),
			'link' => $link->getLink(),
		], $links);

		return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
	}
}
