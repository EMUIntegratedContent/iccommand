<?php

namespace App\Controller\CrimeLog;

use App\Entity\CrimeLog\CrimeLog;
use App\Service\CrimeLogService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * The index page of the crimelog.
 */
class CrimeLogController extends AbstractController
{
	private CrimeLogService $service;
	private ManagerRegistry $doctrine;

	/**
	 * The constructor of the controller for the crimelog.
	 * @param CrimeLogService $service The service of the crimelog.
	 */
	public function __construct(CrimeLogService $service, ManagerRegistry $doctrine)
	{
		$this->service = $service;
		$this->doctrine = $doctrine;
	}

	/**
	 * The index page of the crimelog.
	 */
	#[Route('/crimelog', name: 'crimelog_index')]
	public function index(): Response
	{
		$permissions = json_encode($this->service->getUserCrimeLogPermissions());
		return $this->render('crimelog/index.html.twig', [
			'permissions' => $permissions,
			'controller_name' => 'Master'
		]);
	}

	/**
	 * The management page of the crime log app.
	 */
	#[Route('/crimelog/manage', name: 'crimelog_manage')]
	public function manage(): Response
	{
		return $this->render('crimelog/manage.html.twig', []);
	}
}
