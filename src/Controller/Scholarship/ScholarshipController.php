<?php

namespace App\Controller\Scholarship;

use App\Entity\Scholarship\Scholarship;
use App\Service\ScholarshipService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for the Scholarships application (page rendering).
 * Page access is covered by the ^/scholarships rule in security.yaml.
 */
class ScholarshipController extends AbstractController
{
    private ScholarshipService $service;
    private ManagerRegistry $doctrine;

    public function __construct(ScholarshipService $service, ManagerRegistry $doctrine)
    {
        $this->service = $service;
        $this->doctrine = $doctrine;
    }

    /**
     * The index (list) page.
     */
    #[Route('/scholarships', name: 'scholarship_index')]
    public function index(): Response
    {
        $permissions = json_encode($this->service->getScholarshipPermissions());
        return $this->render('scholarship/index.html.twig', [
            'permissions' => $permissions,
            'controller_name' => 'Scholarship'
        ]);
    }

    /**
     * The create page.
     */
    #[Route('/scholarships/create', name: 'scholarship_create')]
    public function add(): Response
    {
        $permissions = json_encode($this->service->getScholarshipPermissions());
        return $this->render('scholarship/create.html.twig', ['permissions' => $permissions]);
    }

    /**
     * The management page. Declared before the /{id} show route so "manage" isn't
     * captured as an id.
     */
    #[Route('/scholarships/manage', name: 'scholarship_manage')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_SCHOLARSHIP_ADMIN")'))]
    public function manage(): Response
    {
        return $this->render('scholarship/manage.html.twig', []);
    }

    /**
     * The edit page.
     */
    #[Route('/scholarships/{id}/edit', name: 'scholarship_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id): Response
    {
        $this->findScholarshipOr404($id);

        $permissions = json_encode($this->service->getScholarshipPermissions());

        return $this->render('scholarship/edit.html.twig', [
            'id' => $id,
            'permissions' => $permissions
        ]);
    }

    /**
     * The show (read-only) page.
     */
    #[Route('/scholarships/{id}', name: 'scholarship_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $this->findScholarshipOr404($id);

        $permissions = json_encode($this->service->getScholarshipPermissions());

        return $this->render('scholarship/show.html.twig', [
            'id' => $id,
            'permissions' => $permissions
        ]);
    }

    /**
     * Find a scholarship by ID or throw a 404.
     * @param int $id
     * @return Scholarship
     */
    private function findScholarshipOr404(int $id): Scholarship
    {
        $scholarship = $this->doctrine->getRepository(Scholarship::class)->find($id);

        if (!$scholarship) {
            throw $this->createNotFoundException('This scholarship does not exist.');
        }

        return $scholarship;
    }
}
