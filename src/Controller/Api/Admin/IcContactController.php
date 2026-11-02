<?php

namespace App\Controller\Api\Admin;

use App\Entity\Ic\IcContact;
use App\Entity\Ic\IcContactType;
use App\Service\IcService;
use App\Util\RequestHelper;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * CRUD for the contacts shared across IC Command apps (ic_contacts).
 * Prefix /api/admin/ (routes.yaml); global admins only.
 */
class IcContactController extends AbstractController
{
    /**
     * Optional text fields (payload key => entity setter). Applied when the key is present.
     */
    private const STRING_FIELDS = [
        'lastName' => 'setLastName',
        'firstName' => 'setFirstName',
        'organization' => 'setOrganization',
        'email' => 'setEmail',
        'phone' => 'setPhone',
        'room' => 'setRoom',
        'area' => 'setArea',
        'url' => 'setUrl',
        'foragerUrl' => 'setForagerUrl',
        'imageUrl' => 'setImageUrl',
    ];

    public function __construct(
        private IcService $service,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/contacts', methods: ['GET'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function listAction(Request $request): Response
    {
        [$page, $limit] = RequestHelper::pagination($request, 50);
        $searchTerm = $request->query->get('searchterm');

        $result = $this->service->getContactsPagination($page, $limit, $searchTerm);
        return new Response(json_encode($result), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/contacts/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function getAction(int $id): Response
    {
        $contact = $this->service->getContactWithUsage($id);
        if ($contact === null) {
            return new Response(json_encode("Contact not found."), 404, ["Content-Type" => "application/json"]);
        }
        return new Response(json_encode($contact), 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/contacts', methods: ['POST'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function createAction(Request $request): Response
    {
        $contact = new IcContact();
        $this->applyFields($contact, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($contact);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($contact);
        return new Response($this->serialize($contact), 201, ["Content-Type" => "application/json"]);
    }

    #[Route('/contacts/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function updateAction(int $id, Request $request): Response
    {
        $contact = $this->service->getContact($id);
        if ($contact === null) {
            return new Response(json_encode("Contact not found."), 404, ["Content-Type" => "application/json"]);
        }

        $this->applyFields($contact, json_decode($request->getContent(), true) ?? []);

        $errors = $this->service->validate($contact);
        if (count($errors) > 0) {
            return new Response($this->serializer->serialize($errors, "json"), 422, ["Content-Type" => "application/json"]);
        }

        $this->service->save($contact);
        return new Response($this->serialize($contact), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Refuses (409) while any college, department, program, scholarship or building still
     * points at the contact.
     */
    #[Route('/contacts/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GLOBAL_ADMIN')]
    public function deleteAction(int $id): Response
    {
        $contact = $this->service->getContact($id);
        if ($contact === null) {
            return new Response(json_encode("Contact not found."), 404, ["Content-Type" => "application/json"]);
        }

        $blocker = $this->service->contactDeleteBlocker($id);
        if ($blocker !== null) {
            return $this->inUse($blocker);
        }

        try {
            $this->service->delete($contact);
        } catch (ForeignKeyConstraintViolationException) {
            return $this->inUse(sprintf("%s can't be deleted because it is still in use.", $contact->getDisplayName()));
        }

        return new Response(null, 204);
    }

    /* ***************************** Helpers ***************************** */

    /**
     * An unknown or blank type leaves the type unset so validation reports it.
     */
    private function applyFields(IcContact $contact, array $data): void
    {
        if (array_key_exists('type', $data)) {
            $contact->setType(is_string($data['type']) ? IcContactType::tryFrom($data['type']) : null);
        }

        foreach (self::STRING_FIELDS as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $contact->$setter(RequestHelper::optionalString($data[$key]));
            }
        }
    }

    private function inUse(string $message): Response
    {
        return new Response(json_encode([
            'error' => 'in_use',
            'message' => $message,
        ]), 409, ["Content-Type" => "application/json"]);
    }

    private function serialize($data): string
    {
        return $this->serializer->serialize($data, "json", ['groups' => 'ic']);
    }
}
