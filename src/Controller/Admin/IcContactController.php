<?php

namespace App\Controller\Admin;

use App\Service\IcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin pages for the contacts shared across IC Command apps (ic_contacts).
 */
class IcContactController extends AbstractController
{
    public function __construct(private IcService $service)
    {
    }

    /**
     * The index (list) page.
     */
    #[Route('/admin/contacts', name: 'admin_contacts_index')]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function index(): Response
    {
        return $this->render('admin/contacts/index.html.twig');
    }

    /**
     * The create page.
     */
    #[Route('/admin/contacts/create', name: 'admin_contacts_create')]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function create(): Response
    {
        return $this->render('admin/contacts/create.html.twig');
    }

    /**
     * The edit page.
     */
    #[Route('/admin/contacts/{id}/edit', name: 'admin_contacts_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function edit(int $id): Response
    {
        if ($this->service->getContact($id) === null) {
            throw $this->createNotFoundException('This contact does not exist.');
        }

        return $this->render('admin/contacts/edit.html.twig', ['id' => $id]);
    }
}
