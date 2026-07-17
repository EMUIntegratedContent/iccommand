<?php

namespace App\Controller\Api\SocialMedia;

use App\Entity\SocialMedia;
use App\Service\SocialMediaService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * API SocialMediaController
 * Manages social media link entities with the actions of getting, adding,
 * updating, and deleting.
 */
class SocialMediaController extends AbstractController
{
    private SocialMediaService $service;
    private LoggerInterface $logger;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    public function __construct(SocialMediaService $service, LoggerInterface $logger, ManagerRegistry $doctrine, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->service = $service;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * Deletes the social media entity for the specified ID.
     * @param $id The ID of the entity.
     * @return Response The message, the status code, and the HTTP headers.
     */
    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SOCIAL_USER")'))]
    public function deleteSocialMediaAction($id): Response
    {
        $socialMedia = $this->doctrine->getRepository(SocialMedia::class)->find($id);

        if (!$socialMedia) {
            return new Response("Social media entity not found.", 404, array("Content-Type" => "application/json"));
        }

        $this->em->remove($socialMedia);
        $this->em->flush();

        return new Response("Social media entity has been deleted.", 204, array("Content-Type" => "application/json"));
    }

    /**
     * Gets paginated social media entities (ordered by name).
     * @return Response Entities, the status code, and the HTTP headers.
     */
    #[Route('/list', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SOCIAL_USER")'))]
    public function getSocialMediaListAction(Request $request): Response
    {
        $page = $request->query->get('page') ?? 1;
        $pageSize = $request->query->get('limit') ?? 10;
        $searchTerm = $request->query->get('search') ?? '';

        $socialMedia = $this->service->getSocialMediaPagination($page, $pageSize, $searchTerm);

        $serialized = $this->serializer->serialize($socialMedia, "json", ['groups' => 'social']);

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Search social media entities by name (used for the list page typeahead).
     * @param Request $request
     * @return Response
     */
    #[Route('/search', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SOCIAL_USER")'))]
    public function searchSocialMediaAction(Request $request): Response
    {
        $searchTerm = $request->query->get('searchterm');

        $socialMedia = $this->service->getSocialMediaByName($searchTerm);

        $serialized = $this->serializer->serialize($socialMedia, "json", ['groups' => 'social']);

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Gets the social media entity by the specified ID.
     * @param $id The ID of the entity.
     * @return Response The entity, the status code, and the HTTP headers.
     */
    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SOCIAL_USER")'))]
    public function getSocialMediaAction($id): Response
    {
        $socialMedia = $this->doctrine->getRepository(SocialMedia::class)->findOneBy(["id" => $id]);

        if (!$socialMedia) {
            return new Response("The social media entity you requested was not found.", 404, array("Content-Type" => "application/json"));
        }

        $serialized = $this->serializer->serialize($socialMedia, "json", ['groups' => 'social']);

        return new Response($serialized, 200, array("Content-Type" => "application/json"));
    }

    /**
     * Posts the new social media entity from the specified request.
     * @param Request $request The holder of the information about the new entity.
     * @return Response The entity, the status code, and the HTTP headers.
     */
    #[Route('/', methods: ['POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SOCIAL_USER")'))]
    public function postSocialMediaAction(Request $request): Response
    {
        $socialMedia = new SocialMedia();

        $this->applyFields($socialMedia, $request);

        $errors = $this->service->validate($socialMedia);

        if (count($errors) > 0) {
            $serialized = $this->serializer->serialize($errors, "json", ['groups' => 'social']);
            return new Response($serialized, 422, array("Content-Type" => "application/json"));
        }

        $this->em->persist($socialMedia);
        $this->em->flush();

        $serialized = $this->serializer->serialize($socialMedia, "json", ['groups' => 'social']);

        return new Response($serialized, 201, array("Content-Type" => "application/json"));
    }

    /**
     * Updates the social media entity from the specified request.
     * @param Request $request The holder of the information about the updated entity.
     * @return Response The entity, the status code, and the HTTP headers.
     */
    #[Route('/', methods: ['PUT'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN") or is_granted("ROLE_SOCIAL_USER")'))]
    public function putSocialMediaAction(Request $request): Response
    {
        $socialMedia = $this->doctrine->getRepository(SocialMedia::class)->find($request->request->get("id"));

        if (!$socialMedia) {
            return new Response("Social media entity not found.", 404, array("Content-Type" => "application/json"));
        }

        $this->applyFields($socialMedia, $request);

        $errors = $this->service->validate($socialMedia);

        if (count($errors) > 0) {
            $serialized = $this->serializer->serialize($errors, "json", ['groups' => 'social']);
            return new Response($serialized, 422, array("Content-Type" => "application/json"));
        }

        $this->em->persist($socialMedia);
        $this->em->flush();

        $serialized = $this->serializer->serialize($socialMedia, "json", ['groups' => 'social']);

        return new Response($serialized, 201, array("Content-Type" => "application/json"));
    }

    /**
     * Applies the request's form fields to the entity. Empty URL strings are
     * stored as null so they don't trip the Assert\Url validation.
     */
    private function applyFields(SocialMedia $socialMedia, Request $request): void
    {
        $socialMedia->setName($request->request->get("name"));
        $socialMedia->setFacebookUrl($this->nullIfBlank($request->request->get("facebook_url")));
        $socialMedia->setXUrl($this->nullIfBlank($request->request->get("x_url")));
        $socialMedia->setYoutubeUrl($this->nullIfBlank($request->request->get("youtube_url")));
        $socialMedia->setInstagramUrl($this->nullIfBlank($request->request->get("instagram_url")));
        $socialMedia->setLinkedinUrl($this->nullIfBlank($request->request->get("linkedin_url")));
        $socialMedia->setTiktokUrl($this->nullIfBlank($request->request->get("tiktok_url")));
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;
        return $value === '' ? null : $value;
    }
}
