<?php

namespace App\Controller\MultimediaRequest;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
use App\Service\MultimediaRequestService;

class MultimediaRequestAssigneeController extends AbstractController
{
    private $service;
    private $doctrine;

    public function __construct(MultimediaRequestService $service, ManagerRegistry $doctrine){
        $this->service = $service;
        $this->doctrine = $doctrine;
    }
    /**
     * @Route("/multimediarequests/assignees", name="multimediarequests_assignee_home")
     */
    public function index()
    {
      $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
      return $this->render('multimedia_request/assignees/index.html.twig', ['permissions' => $permissions]);
    }

    /**
     * @Route("/multimediarequests/assignees/create", name="multimediarequests_assignee_create")
     */
    public function add(){
      $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
      return $this->render('multimedia_request/assignees/create.html.twig', ['permissions' => $permissions]);
    }

    /**
     * @Route("/multimediarequests/assignees/{id}", name="multimediarequests_assignee_show")
     */
    public function show($id){
      $assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($id);
      if (!$assignee) {
        throw $this->createNotFoundException('This assignee does not exist.');
      }

      $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
      return $this->render('multimedia_request/assignees/show.html.twig', ['id' => $id, 'permissions' => $permissions]);
    }

    /**
     * @Route("/multimediarequests/assignees/{id}/edit", name="multimediarequests_assignee_edit")
     */
    public function edit($id){
      $assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($id);
      if (!$assignee) {
        throw $this->createNotFoundException('This assignee does not exist.');
      }

      $permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
      return $this->render('multimedia_request/assignees/edit.html.twig', ['id' => $id, 'permissions' => $permissions]);
    }
}
