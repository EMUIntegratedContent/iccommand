<?php

namespace App\Controller\Admin;

use App\Service\IcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin pages for the departments shared by the Programs and Scholarships apps (ic_departments).
 */
class IcDepartmentController extends AbstractController
{
    public function __construct(private IcService $service)
    {
    }

    /**
     * The index (list) page.
     */
    #[Route('/admin/departments', name: 'admin_departments_index')]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function index(): Response
    {
        return $this->render('admin/departments/index.html.twig');
    }

    /**
     * The create page.
     */
    #[Route('/admin/departments/create', name: 'admin_departments_create')]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function create(): Response
    {
        return $this->render('admin/departments/create.html.twig');
    }

    /**
     * The edit page.
     */
    #[Route('/admin/departments/{id}/edit', name: 'admin_departments_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function edit(int $id): Response
    {
        if ($this->service->getDepartment($id) === null) {
            throw $this->createNotFoundException('This department does not exist.');
        }

        return $this->render('admin/departments/edit.html.twig', ['id' => $id]);
    }
}
