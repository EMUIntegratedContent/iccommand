<?php

namespace App\Controller\Contacts;

use App\Service\IcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for the Contacts application (page rendering) over the contacts shared
 * across IC Command apps (ic_contacts). Page access is covered by the ^/contacts rule in
 * security.yaml.
 */
class ContactController extends AbstractController
{
    public function __construct(private IcService $service)
    {
    }

    /**
     * The index (list) page.
     */
    #[Route('/contacts', name: 'contacts_index')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CONTACTS_VIEW")'))]
    public function index(): Response
    {
        return $this->render('contacts/index.html.twig', ['permissions' => $this->permissions()]);
    }

    /**
     * The create page.
     */
    #[Route('/contacts/create', name: 'contacts_create')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CONTACTS_EDIT")'))]
    public function create(): Response
    {
        return $this->render('contacts/create.html.twig', ['permissions' => $this->permissions()]);
    }

    /**
     * The management page. Declared before the /{id} show route so "manage" isn't captured
     * as an id.
     */
    #[Route('/contacts/manage', name: 'contacts_manage')]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CONTACTS_ADMIN")'))]
    public function manage(): Response
    {
        return $this->render('contacts/manage.html.twig', []);
    }

    /**
     * The edit page.
     */
    #[Route('/contacts/{id}/edit', name: 'contacts_edit', requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CONTACTS_EDIT")'))]
    public function edit(int $id): Response
    {
        $this->findContactOr404($id);

        return $this->render('contacts/edit.html.twig', ['id' => $id, 'permissions' => $this->permissions()]);
    }

    /**
     * The show (read-only) page.
     */
    #[Route('/contacts/{id}', name: 'contacts_show', requirements: ['id' => '\d+'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CONTACTS_VIEW")'))]
    public function show(int $id): Response
    {
        $this->findContactOr404($id);

        return $this->render('contacts/show.html.twig', ['id' => $id, 'permissions' => $this->permissions()]);
    }

    private function permissions(): string
    {
        return json_encode($this->service->getContactPermissions());
    }

    private function findContactOr404(int $id): void
    {
        if ($this->service->getContact($id) === null) {
            throw $this->createNotFoundException('This contact does not exist.');
        }
    }
}
