<?php

namespace App\Controller\MultimediaRequest;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\MultimediaRequest\MultimediaRequest;
use App\Service\MultimediaRequestService;

class MultimediaRequestController extends Controller
{
    private $service;

    public function __construct(MultimediaRequestService $service)
    {
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

    /**
     * @Route("/multimediarequests/create", name="multimediarequests_create")
     */
    public function add()
    {
        $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
        return $this->render('multimedia_request/create.html.twig', ['permissions' => $permissions]);
    }

    /**
     * @Route("/multimediarequests/{id}", name="multimediarequests_show")
     */
    public function show($id)
    {
        $multimediaRequest = $this->getDoctrine()->getRepository(MultimediaRequest::class)->find($id);
        if (!$multimediaRequest) {
            throw $this->createNotFoundException('This multimedia request does not exist.');
        }
        $requestType = $multimediaRequest->getRequestType();

        $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
        return $this->render('multimedia_request/show.html.twig', ['id' => $id, 'requestType' => $requestType, 'permissions' => $permissions]);
    }

    /**
     * @Route("/multimediarequests/{id}/edit", name="multimediarequests_edit")
     */
    public function edit($id)
    {
        $multimediaRequest = $this->getDoctrine()->getRepository(MultimediaRequest::class)->find($id);
        if (!$multimediaRequest) {
            throw $this->createNotFoundException('This request does not exist.');
        }
        $requestType = $multimediaRequest->getRequestType();

        $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
        return $this->render('multimedia_request/edit.html.twig', ['id' => $id, 'requestType' => $requestType, 'permissions' => $permissions]);
    }
}
