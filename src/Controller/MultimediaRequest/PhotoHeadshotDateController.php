<?php

namespace App\Controller\MultimediaRequest;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\MultimediaRequest\MultimediaRequest;
use App\Service\MultimediaRequestService;

class PhotoHeadshotDateController extends Controller
{
    private $service;

    public function __construct(MultimediaRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/multimediarequests/headshots/scheduler", name="multimediarequests_headshots_home")
     */
    public function index()
    {
        $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
        return $this->render('multimedia_request/headshots/index.html.twig', ['permissions' => $permissions]);
    }

}
