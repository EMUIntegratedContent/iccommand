<?php

namespace App\Controller\MultimediaRequest;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Service\MultimediaRequestService;

class MultimediaRequestController extends Controller
{
    private $service;

    public function __construct(MultimediaRequestService $service){
        $this->service = $service;
    }

    /**
     * @Route("/multimediarequests", name="multimediarequest_home")
     */
    public function index()
    {
        $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
        return $this->render('multimedia_request/index.html.twig', ['permissions' => $permissions]);
    }
}
