<?php

namespace App\Controller\Map;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\MapItemService;

class MapController extends AbstractController
{
	private MapItemService $service;

	public function __construct(MapItemService $service){
		$this->service = $service;
	}

	#[Route('/map', name: 'map_index')]
	public function index()
	{
		return $this->render('map/index.html.twig', []);
	}

	#[Route('/map/manage', name: 'map_manage')]
	#[IsGranted('ROLE_MAP_ADMIN')]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	public function manage()
	{
		return $this->render('map/manage.html.twig', []);
	}
}
