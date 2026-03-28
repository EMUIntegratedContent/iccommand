<?php
namespace App\Controller\GradCas;

use App\Entity\GradCas\GradCasCycle;
use App\Entity\GradCas\GradCasLink;
use App\Service\GradCasService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GradCasController extends AbstractController {
	private GradCasService $service;
	private ManagerRegistry $doctrine;

	public function __construct(GradCasService $service, ManagerRegistry $doctrine)
	{
		$this->service = $service;
		$this->doctrine = $doctrine;
	}

	#[Route('/gradcas', name: 'gradcas_index')]
	public function index(): Response
	{
		$permissions = json_encode($this->service->getUserGradCasPermissions());
		$currentCycle = $this->doctrine->getRepository(GradCasCycle::class)->findCurrentCycle();
		$currentCycleId = $currentCycle ? $currentCycle->getId() : 0;

		return $this->render('gradcas/index.html.twig', [
			'permissions' => $permissions,
			'currentCycleId' => $currentCycleId
		]);
	}

	#[Route('/gradcas/cycles', name: 'gradcas_cycles')]
	#[IsGranted('ROLE_GRADCAS_ADMIN')]
	public function cycles(): Response
	{
		$permissions = json_encode($this->service->getUserGradCasPermissions());
		return $this->render('gradcas/cycles.html.twig', [
			'permissions' => $permissions
		]);
	}

	#[Route('/gradcas/cycle/create', name: 'gradcas_cycle_create')]
	#[IsGranted('ROLE_GRADCAS_ADMIN')]
	public function cycleCreate(): Response
	{
		$permissions = json_encode($this->service->getUserGradCasPermissions());
		return $this->render('gradcas/cycle_create.html.twig', [
			'permissions' => $permissions
		]);
	}

	#[Route('/gradcas/cycle/{id}/edit', name: 'gradcas_cycle_edit')]
	#[IsGranted('ROLE_GRADCAS_ADMIN')]
	public function cycleEdit(int $id): Response
	{
		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($id);
		if (!$cycle) {
			throw $this->createNotFoundException('This cycle does not exist.');
		}

		$permissions = json_encode($this->service->getUserGradCasPermissions());
		return $this->render('gradcas/cycle_edit.html.twig', [
			'id' => $id,
			'permissions' => $permissions
		]);
	}

	#[Route('/gradcas/link/create/{cycleId}', name: 'gradcas_link_create')]
	#[IsGranted('ROLE_GRADCAS_EDIT')]
	public function linkCreate(int $cycleId): Response
	{
		$cycle = $this->doctrine->getRepository(GradCasCycle::class)->find($cycleId);
		if (!$cycle) {
			throw $this->createNotFoundException('This cycle does not exist.');
		}

		$permissions = json_encode($this->service->getUserGradCasPermissions());
		return $this->render('gradcas/link_create.html.twig', [
			'cycleId' => $cycleId,
			'cycleName' => $cycle->getCycleName(),
			'permissions' => $permissions
		]);
	}

	#[Route('/gradcas/link/{id}/edit', name: 'gradcas_link_edit')]
	#[IsGranted('ROLE_GRADCAS_EDIT')]
	public function linkEdit(int $id): Response
	{
		$link = $this->doctrine->getRepository(GradCasLink::class)->find($id);
		if (!$link) {
			throw $this->createNotFoundException('This link does not exist.');
		}

		$permissions = json_encode($this->service->getUserGradCasPermissions());
		return $this->render('gradcas/link_edit.html.twig', [
			'id' => $id,
			'permissions' => $permissions
		]);
	}

	#[Route('/gradcas/manage', name: 'gradcas_manage')]
	#[IsGranted('ROLE_GRADCAS_ADMIN')]
	public function manage(): Response
	{
		return $this->render('gradcas/manage.html.twig', []);
	}
}
