<?php

namespace App\Controller\Api\Ic;

use App\Service\IcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The contact picker's options. Prefix /api/ic/ (routes.yaml). Separate from the admin API
 * because map, program and scholarship editors use the picker too. Each module's CREATE role
 * also covers its editors and admins through the role hierarchy.
 */
class IcContactOptionsController extends AbstractController
{
    public function __construct(private IcService $service)
    {
    }

    /**
     * Every contact (id, displayName, type, email), sorted by name.
     */
    #[Route('/contacts/options', methods: ['GET'])]
    #[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_CREATE") or is_granted("ROLE_PROGRAMS_CREATE") or is_granted("ROLE_SCHOLARSHIP_CREATE")'))]
    public function getOptionsAction(): Response
    {
        return new Response(json_encode($this->service->getContactsForDropdown()), 200, ["Content-Type" => "application/json"]);
    }
}
