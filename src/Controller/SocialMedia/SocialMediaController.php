<?php

namespace App\Controller\SocialMedia;

use App\Entity\SocialMedia\SocialMedia;
use App\Service\SocialMediaService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

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
     * The management page — lets global admins and app admins grant/revoke user
     * access to the Social Media Links application. Declared before the /{id}
     * show route so "manage" isn't captured as an id.
     */
    #[Route('/social-media/manage', name: 'social_media_manage')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SOCIAL_ADMIN")'))]
    public function manage(): Response
    {
        return $this->render('socialmedia/manage.html.twig', []);
    }

    /**
     * The edit page.
     */
    #[Route('/social-media/{id}/edit', name: 'social_media_edit')]
    public function edit(int $id): Response
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
    public function show(int $id): Response
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
