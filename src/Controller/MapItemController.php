<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MapItemController extends Controller
{
    /**
     * @Route("/map/items", name="map_items")
     */
    public function index()
    {
        return $this->render('map_item/index.html.twig', [
            'controller_name' => 'MapItemController',
        ]);
    }
}
