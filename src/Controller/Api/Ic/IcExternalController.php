<?php

namespace App\Controller\Api\Ic;

use App\Service\IcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public (unauthenticated) read endpoints for the shared colleges and departments
 * (ic_colleges / ic_departments). Prefix /api/external/ic/ (routes.yaml). Access is granted
 * by the `^/api/external` = PUBLIC_ACCESS rule in security.yaml — no #[IsGranted] here.
 * Same output as /api/scholarships/colleges and /api/scholarships/departments.
 */
class IcExternalController extends AbstractController
{
    public function __construct(private IcService $service)
    {
    }

    /**
     * Every college (id, college, url), sorted by name.
     */
    #[Route('/colleges', methods: ['GET'])]
    public function getCollegesAction(): Response
    {
        return new Response(json_encode($this->service->getCollegesForDropdown()), 200, ["Content-Type" => "application/json"]);
    }

    /**
     * Every department (id, department, college_id), sorted by name.
     */
    #[Route('/departments', methods: ['GET'])]
    public function getDepartmentsAction(): Response
    {
        return new Response(json_encode($this->service->getDepartmentsForDropdown()), 200, ["Content-Type" => "application/json"]);
    }
}
