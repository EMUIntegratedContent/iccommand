<?php

namespace App\Controller\Admin;

use App\Service\IcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin pages for the colleges shared by the Programs and Scholarships apps (ic_colleges).
 */
class IcCollegeController extends AbstractController
{
    public function __construct(private IcService $service)
    {
    }

    /**
     * The index (list) page.
     */
    #[Route('/admin/colleges', name: 'admin_colleges_index')]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function index(): Response
    {
        return $this->render('admin/colleges/index.html.twig');
    }

    /**
     * The create page.
     */
    #[Route('/admin/colleges/create', name: 'admin_colleges_create')]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function create(): Response
    {
        return $this->render('admin/colleges/create.html.twig');
    }

    /**
     * The edit page.
     */
    #[Route('/admin/colleges/{id}/edit', name: 'admin_colleges_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function edit(int $id): Response
    {
        if ($this->service->getCollege($id) === null) {
            throw $this->createNotFoundException('This college does not exist.');
        }

        return $this->render('admin/colleges/edit.html.twig', ['id' => $id]);
    }
}
