<?php

namespace App\Controller\Api\PhotoRequest;

use App\Security\ExternalApiToken;
use App\Service\PhotoRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Photo request submission from the emich.edu form (server-to-server).
 * No login: the caller sends the shared API key in the X-API-Key header.
 * EXTERNAL_API_TOKEN_MODE=log still accepts keyless calls (and logs them) during rollout.
 */
class PhotoRequestExternalController extends AbstractController
{
	public function __construct(
		private PhotoRequestService $service,
		private EntityManagerInterface $em,
		private SerializerInterface $serializer,
	) {
	}

	#[Route('/', methods: ['POST'])]
	public function submitAction(Request $request, ExternalApiToken $apiToken): Response
	{
		if (!$apiToken->allows($request, true)) {
			return new JsonResponse(['error' => 'Missing or invalid API token.'], 401);
		}

		$photoRequest = $this->service->createFromData($request->request->all());

		$errors = $this->service->validate($photoRequest);
		if (count($errors) > 0) {
			return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
		}

		$this->em->persist($photoRequest);
		$this->em->flush();

		return new Response($this->serializer->serialize($photoRequest, "json", ['groups' => ['photos']]), 201, ["Content-Type" => "application/json"]);
	}
}
