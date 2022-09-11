<?php

namespace App\Controller\Map;

use App\Service\MapItemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MapController extends AbstractController
{
    private $service;

    public function __construct(MapItemService $service){
        $this->service = $service;
    }

    /**
     * @Route("/map", name="map_index")
     */
    public function index()
    {
      return $this->render('map/index.html.twig', []);
    }

    /**
     * @Route("/map/manage", name="map_manage")
     * @Security("has_role('ROLE_MAP_ADMIN') or has_role('ROLE_GLOBAL_ADMIN')")
     */
    public function manage()
    {
      return $this->render('map/manage.html.twig', []);
    }
}
