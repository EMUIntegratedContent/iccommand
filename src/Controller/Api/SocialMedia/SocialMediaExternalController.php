<?php

namespace App\Controller\Api\SocialMedia;

use App\Entity\SocialMedia\SocialMedia;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SocialMediaExternalController extends AbstractController
{
	private ManagerRegistry $doctrine;
	private SerializerInterface $serializer;

	public function __construct(ManagerRegistry $doctrine, SerializerInterface $serializer)
	{
		$this->doctrine = $doctrine;
		$this->serializer = $serializer;
	}

	#[Route('/all', methods: ['GET'])]
	public function getSocialMediaAction(): Response
	{
		$socialMedia = $this->doctrine->getRepository(SocialMedia::class)->findAll();

		// Just return the name, id, and links for the external API
		$result = array_map(fn($item) => [
			'name' => $item->getName(),
			'id' => $item->getId(),
			'links' => [
				'facebook' => $item->getFacebookUrl(),
				'x' => $item->getXUrl(),
				'youtube' => $item->getYoutubeUrl(),
				'instagram' => $item->getInstagramUrl(),
				'linkedin' => $item->getLinkedinUrl(),
				'tiktok' => $item->getTiktokUrl(),
				'snapchat' => $item->getSnapchatUrl(),
			],
		], $socialMedia);

		$serialized = $this->serializer->serialize($result, "json");
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}

	#[Route('/{id}', methods: ['GET'])]
	public function getSocialMediaByIdAction(int $id): Response
	{
		$socialMedia = $this->doctrine->getRepository(SocialMedia::class)->find($id);

		if (!$socialMedia) {
			return new Response(json_encode("Social media not found."), 404, ["Content-Type" => "application/json"]);
		}

		$result = [
			'name' => $socialMedia->getName(),
			'id' => $socialMedia->getId(),
			'links' => [
				'facebook' => $socialMedia->getFacebookUrl(),
				'x' => $socialMedia->getXUrl(),
				'youtube' => $socialMedia->getYoutubeUrl(),
				'instagram' => $socialMedia->getInstagramUrl(),
				'linkedin' => $socialMedia->getLinkedinUrl(),
				'tiktok' => $socialMedia->getTiktokUrl(),
				'snapchat' => $socialMedia->getSnapchatUrl(),
			],
		];

		$serialized = $this->serializer->serialize($result, "json");
		return new Response($serialized, 200, ["Content-Type" => "application/json"]);
	}
}
