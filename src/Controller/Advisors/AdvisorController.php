<?php

namespace App\Controller\Advisors;

use App\Service\IcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for the Advisors application (page rendering) over the advisors shared
 * across IC Command apps (ic_advisors). Page access is covered by the ^/advisors rule in
 * security.yaml.
 */
class AdvisorController extends AbstractController
{
    public function __construct(private IcService $service)
    {
    }

    /**
     * The index (list) page.
     */
    #[Route('/advisors', name: 'advisors_index')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_VIEW")'))]
    public function index(): Response
    {
        return $this->render('advisors/index.html.twig', ['permissions' => $this->permissions()]);
    }

    /**
     * The create page.
     */
    #[Route('/advisors/create', name: 'advisors_create')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function create(): Response
    {
        return $this->render('advisors/create.html.twig', ['permissions' => $this->permissions()]);
    }

    /**
     * The management page. Declared before the /{id} show route so "manage" isn't captured
     * as an id.
     */
    #[Route('/advisors/manage', name: 'advisors_manage')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_ADMIN")'))]
    public function manage(): Response
    {
        return $this->render('advisors/manage.html.twig', []);
    }

    /**
     * The edit page.
     */
    #[Route('/advisors/{id}/edit', name: 'advisors_edit', requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_EDIT")'))]
    public function edit(int $id): Response
    {
        $this->findAdvisorOr404($id);

        return $this->render('advisors/edit.html.twig', ['id' => $id, 'permissions' => $this->permissions()]);
    }

    /**
     * The show (read-only) page.
     */
    #[Route('/advisors/{id}', name: 'advisors_show', requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_ADVISORS_VIEW")'))]
    public function show(int $id): Response
    {
        $this->findAdvisorOr404($id);

        return $this->render('advisors/show.html.twig', ['id' => $id, 'permissions' => $this->permissions()]);
    }

    private function permissions(): string
    {
        return json_encode($this->service->getAdvisorPermissions());
    }

    private function findAdvisorOr404(int $id): void
    {
        if ($this->service->getAdvisor($id) === null) {
            throw $this->createNotFoundException('This advisor does not exist.');
        }
    }
}
