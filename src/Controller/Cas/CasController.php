<?php
namespace App\Controller\Cas;

use App\Entity\Cas\CasCycle;
use App\Entity\Cas\CasLink;
use App\Service\CasService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CasController extends AbstractController {
	private CasService $service;
	private ManagerRegistry $doctrine;

	public function __construct(CasService $service, ManagerRegistry $doctrine)
	{
		$this->service = $service;
		$this->doctrine = $doctrine;
	}

	#[Route('/cas', name: 'cas_index')]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CAS_VIEW")'))]
	public function index(): Response
	{
		$permissions = json_encode($this->service->getUserCasPermissions());
		$currentCycle = $this->doctrine->getRepository(CasCycle::class)->findCurrentCycle();
		$currentCycleId = $currentCycle ? $currentCycle->getId() : 0;

		return $this->render('cas/index.html.twig', [
			'permissions' => $permissions,
			'currentCycleId' => $currentCycleId
		]);
	}

	#[Route('/cas/cycles', name: 'cas_cycles')]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CAS_ADMIN")'))]
	public function cycles(): Response
	{
		$permissions = json_encode($this->service->getUserCasPermissions());
		return $this->render('cas/cycles.html.twig', [
			'permissions' => $permissions
		]);
	}

	#[Route('/cas/cycle/create', name: 'cas_cycle_create')]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CAS_ADMIN")'))]
	public function cycleCreate(): Response
	{
		$permissions = json_encode($this->service->getUserCasPermissions());
		return $this->render('cas/cycle_create.html.twig', [
			'permissions' => $permissions
		]);
	}

	#[Route('/cas/cycle/{id}/edit', name: 'cas_cycle_edit')]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CAS_ADMIN")'))]
	public function cycleEdit(int $id): Response
	{
		$cycle = $this->doctrine->getRepository(CasCycle::class)->find($id);
		if (!$cycle) {
			throw $this->createNotFoundException('This cycle does not exist.');
		}

		$permissions = json_encode($this->service->getUserCasPermissions());
		return $this->render('cas/cycle_edit.html.twig', [
			'id' => $id,
			'permissions' => $permissions
		]);
	}

	#[Route('/cas/link/create/{cycleId}', name: 'cas_link_create')]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CAS_ADMIN") or is_granted("ROLE_CAS_EDIT")'))]
	public function linkCreate(int $cycleId): Response
	{
		$cycle = $this->doctrine->getRepository(CasCycle::class)->find($cycleId);
		if (!$cycle) {
			throw $this->createNotFoundException('This cycle does not exist.');
		}

		$permissions = json_encode($this->service->getUserCasPermissions());
		return $this->render('cas/link_create.html.twig', [
			'cycleId' => $cycleId,
			'cycleName' => $cycle->getCycleName(),
			'permissions' => $permissions
		]);
	}

	#[Route('/cas/link/{id}/edit', name: 'cas_link_edit')]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CAS_ADMIN") or is_granted("ROLE_CAS_EDIT")'))]
	public function linkEdit(int $id): Response
	{
		$link = $this->doctrine->getRepository(CasLink::class)->find($id);
		if (!$link) {
			throw $this->createNotFoundException('This link does not exist.');
		}

		$permissions = json_encode($this->service->getUserCasPermissions());
		return $this->render('cas/link_edit.html.twig', [
			'id' => $id,
			'permissions' => $permissions
		]);
	}

	#[Route('/cas/manage', name: 'cas_manage')]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_CAS_ADMIN")'))]
	public function manage(): Response
	{
		return $this->render('cas/manage.html.twig', []);
	}
}
