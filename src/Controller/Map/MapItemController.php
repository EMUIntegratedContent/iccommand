<?php

namespace App\Controller\Map;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Map\MapItem;

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
      $item = $this->getDoctrine()->getRepository(MapItem::class)->find($id);
      if (!$item) {
        // Just a shortcut for:
        // throw new NotFoundHttpException();
        throw $this->createNotFoundException('This map item does not exist.');
      }
      $itemType = $item->getItemType();

      return $this->render('map/map_item/show.html.twig', ['id' => $id, 'itemType' => $itemType]);
    }

    /**
     * @Route("/map/items/{id}/edit", name="map_items_edit")
     */
    public function edit($id){
      $item = $this->getDoctrine()->getRepository(MapItem::class)->find($id);
      if (!$item) {
        throw $this->createNotFoundException('This map item does not exist.');
      }
      $itemType = $item->getItemType();

      return $this->render('map/map_item/edit.html.twig', ['id' => $id, 'itemType' => $itemType]);
    }
}
