<?php
// NO LONGER USED AS OF 2025 (hadn't been since 2018) - kept for reference

namespace App\Controller\MultimediaRequest;

//use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\Routing\Annotation\Route;
//use App\Entity\MultimediaRequest\MultimediaRequestAssignee;
//use App\Service\MultimediaRequestService;
//use Symfony\Component\HttpFoundation\Response;
//
class MultimediaRequestAssigneeController extends AbstractController
{
//	private MultimediaRequestService $service;
//	private ManagerRegistry $doctrine;
//
//	public function __construct(MultimediaRequestService $service, ManagerRegistry $doctrine){
//		$this->service = $service;
//		$this->doctrine = $doctrine;
//	}
//
//	#[Route('/multimediarequests/assignees', name: 'multimediarequests_assignee_home')]
//	public function index(): Response
//	{
//		$permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
//		return $this->render('multimedia_request/assignees/index.html.twig', ['permissions' => $permissions]);
//	}
//
//	#[Route('/multimediarequests/assignees/create', name: 'multimediarequests_assignee_create')]
//	public function add(): Response
//	{
//		$permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
//		return $this->render('multimedia_request/assignees/create.html.twig', ['permissions' => $permissions]);
//	}
//
//	#[Route('/multimediarequests/assignees/{id}', name: 'multimediarequests_assignee_show')]
//	public function show($id): Response
//	{
//		$assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($id);
//		if (!$assignee) {
//			throw $this->createNotFoundException('This assignee does not exist.');
//		}
//
//		$permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
//		return $this->render('multimedia_request/assignees/show.html.twig', ['id' => $id, 'permissions' => $permissions]);
//	}
//
//	#[Route('/multimediarequests/assignees/{id}/edit', name: 'multimediarequests_assignee_edit')]
//	public function edit($id): Response
//	{
//		$assignee = $this->doctrine->getRepository(MultimediaRequestAssignee::class)->find($id);
//		if (!$assignee) {
//			throw $this->createNotFoundException('This assignee does not exist.');
//		}
//
//		$permissions = json_encode($this->service->getUserMultimediaRequestPermissions());
//		return $this->render('multimedia_request/assignees/edit.html.twig', ['id' => $id, 'permissions' => $permissions]);
//	}
}
