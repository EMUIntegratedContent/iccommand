<?php

namespace App\Controller\Map;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Service\MapItemService;

class MapController extends Controller
{
    private $service;

    public function __construct(MapItemService $service){
        $this->service = $service;
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
