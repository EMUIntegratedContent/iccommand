<?php

namespace App\Controller\MultimediaRequest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\MultimediaRequest\MultimediaRequest;
use App\Service\MultimediaRequestService;

class PhotoHeadshotDateController extends AbstractController
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
