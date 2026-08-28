<?php

namespace App\Controller\Api\Scholarship;

use App\Entity\Scholarship\Scholarship;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Public (unauthenticated) read endpoints for scholarships. Access is granted by the
 * `^/api/external` = PUBLIC_ACCESS rule in security.yaml — no #[IsGranted] here.
 * Only active scholarships are exposed.
 */
class ScholarshipExternalController extends AbstractController
{
    /**
     * Serialization context for the public endpoints.
     * Uses the scholarship group, minus the Gedmo audit fields and contactId.
     * Excluded here instead of on the entity so the admin API keeps them.
     */
    private const PUBLIC_CONTEXT = [
        'groups' => 'scholarship',
        AbstractNormalizer::IGNORED_ATTRIBUTES => ['created', 'createdBy', 'updated', 'updatedBy', 'contactId'],
    ];

    private ManagerRegistry $doctrine;
    private SerializerInterface $serializer;

    public function __construct(ManagerRegistry $doctrine, SerializerInterface $serializer)
    {
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
    }

    #[Route('/all', methods: ['GET'])]
    public function getScholarshipsAction(): Response
    {
        $scholarships = $this->doctrine->getRepository(Scholarship::class)
            ->findBy(['active' => true], ['title' => 'ASC']);

        $serialized = $this->serializer->serialize($scholarships, "json", self::PUBLIC_CONTEXT);
        return new Response($serialized, 200, ["Content-Type" => "application/json"]);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getScholarshipAction(int $id): Response
    {
        $scholarship = $this->doctrine->getRepository(Scholarship::class)
            ->findOneBy(['id' => $id, 'active' => true]);

        if (!$scholarship) {
            return new Response(json_encode("Scholarship not found."), 404, ["Content-Type" => "application/json"]);
        }

        $serialized = $this->serializer->serialize($scholarship, "json", self::PUBLIC_CONTEXT);
        return new Response($serialized, 200, ["Content-Type" => "application/json"]);
    }
}
