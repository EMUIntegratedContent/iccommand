<?php

namespace App\Controller\Map;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MapItemController extends Controller
{
    /**
     * @Route("/map/items", name="map_items")
     */
    public function index()
    {
      return $this->render('map/map_item/index.html.twig', []);
    }

    /**
     * @Route("/map/items/create", name="map_items_create")
     */
    public function add(){
      return $this->render('map/map_item/create.html.twig', []);
    }

    /**
     * @Route("/map/items/{id}", name="map_items_show")
     */
    public function show($id){
      return $this->render('map/map_item/show.html.twig', ['id' => $id, 'itemType' => 'bathroom']);
    }

    /**
     * @Route("/map/items/{id}/edit", name="map_items_edit")
     */
    public function edit($id){
      return $this->render('map/map_item/edit.html.twig', ['id' => $id, 'itemType' => 'bathroom']);
    }
}
