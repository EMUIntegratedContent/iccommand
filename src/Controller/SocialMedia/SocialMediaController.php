<?php

namespace App\Controller\SocialMedia;

use App\Entity\SocialMedia;
use App\Service\SocialMediaService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the Social Media Links application (page rendering).
 */
class SocialMediaController extends AbstractController
{
    private SocialMediaService $service;
    private ManagerRegistry $doctrine;

    public function __construct(SocialMediaService $service, ManagerRegistry $doctrine)
    {
        $this->service = $service;
        $this->doctrine = $doctrine;
    }

    /**
     * The index (list) page.
     */
    #[Route('/social-media', name: 'social_media_index')]
    public function index(): Response
    {
        $permissions = json_encode($this->service->getUserSocialMediaPermissions());
        return $this->render('socialmedia/index.html.twig', [
            'permissions' => $permissions,
            'controller_name' => 'SocialMedia'
        ]);
    }

    /**
     * The create page.
     */
    #[Route('/social-media/create', name: 'social_media_create')]
    public function add(): Response
    {
        $permissions = json_encode($this->service->getUserSocialMediaPermissions());
        return $this->render('socialmedia/create.html.twig', ['permissions' => $permissions]);
    }

    /**
     * The edit page.
     */
    #[Route('/social-media/{id}/edit', name: 'social_media_edit')]
    public function edit($id): Response
    {
        $socialMedia = $this->doctrine->getRepository(SocialMedia::class)->find($id);

        if (!$socialMedia) {
            throw $this->createNotFoundException('This social media entity does not exist.');
        }

        $permissions = json_encode($this->service->getUserSocialMediaPermissions());

        return $this->render('socialmedia/edit.html.twig', [
            'id' => $id,
            'permissions' => $permissions
        ]);
    }

    /**
     * The show (read-only) page.
     */
    #[Route('/social-media/{id}', name: 'social_media_show')]
    public function show($id): Response
    {
        $socialMedia = $this->doctrine->getRepository(SocialMedia::class)->find($id);

        if (!$socialMedia) {
            throw $this->createNotFoundException('This social media entity does not exist.');
        }

        $permissions = json_encode($this->service->getUserSocialMediaPermissions());

        return $this->render('socialmedia/show.html.twig', [
            'id' => $id,
            'permissions' => $permissions
        ]);
    }
}
