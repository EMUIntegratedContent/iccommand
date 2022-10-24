<?php

namespace App\Controller\MultimediaRequest;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\MultimediaRequest\MultimediaRequest;
use App\Service\MultimediaRequestService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class MultimediaRequestController extends AbstractController
{
    private MultimediaRequestService $service;
    private ManagerRegistry $doctrine;

    public function __construct(MultimediaRequestService $service, ManagerRegistry $doctrine)
    {
        $this->service = $service;
        $this->doctrine = $doctrine;
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
     * @Route("/multimediarequests/requests", name="multimediarequest_list")
     */
    public function list()
    {
        $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
        return $this->render('multimedia_request/list.html.twig', ['permissions' => $permissions]);
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
     * @Route("/multimediarequests/manage", name="multimediarequests_manage")
     * @Security("is_granted('ROLE_MULTIMEDIA_ADMIN') or is_granted('ROLE_GLOBAL_ADMIN')")
     */
    public function manage()
    {
        return $this->render('multimedia_request/manage.html.twig', []);
    }

    /**
     * @Route("/multimediarequests/{id}", name="multimediarequests_show")
     */
    public function show($id)
    {
        $multimediaRequest = $this->doctrine->getRepository(MultimediaRequest::class)->find($id);
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
        $multimediaRequest = $this->doctrine->getRepository(MultimediaRequest::class)->find($id);
        if (!$multimediaRequest) {
            throw $this->createNotFoundException('This request does not exist.');
        }
        $requestType = $multimediaRequest->getRequestType();

        $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
        return $this->render('multimedia_request/edit.html.twig', ['id' => $id, 'requestType' => $requestType, 'permissions' => $permissions]);
    }
}
