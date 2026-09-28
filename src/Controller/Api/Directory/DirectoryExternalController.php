<?php

namespace App\Controller\Api\Directory;

use App\Service\DirectoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Public, read-only department search for the emich.edu search page.
 * Directory contact details are already public, so no login or API key is required.
 */
class DirectoryExternalController extends AbstractController
{
	public function __construct(private DirectoryService $service, private SerializerInterface $serializer)
	{
	}

	#[Route('/search', methods: ['GET'])]
	public function searchAction(Request $request): Response
	{
		$departments = $this->service->searchDepartments($request->query->get('searchterm'));

		$serialized = $this->serializer->serialize($departments, "json", ['groups' => 'department']);

		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}
}
