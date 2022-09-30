<?php

namespace App\Controller\Map;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Map\MapItem;
use App\Service\MapItemService;

class MapItemController extends AbstractController
{
    private $service;
    private $doctrine;

    public function __construct(MapItemService $service, ManagerRegistry $doctrine){
        $this->service = $service;
        $this->doctrine = $doctrine;
    }
    /**
     * @Route("/map/items", name="map_items")
     */
    public function index()
    {
      $permissions = json_encode($this->service->getUserMapPermissions());
      return $this->render('map/map_item/index.html.twig', ['permissions' => $permissions]);
    }

    /**
     * @Route("/map/items/create", name="map_items_create")
     */
    public function add(){
      $permissions = json_encode($this->service->getUserMapPermissions());
      return $this->render('map/map_item/create.html.twig', ['permissions' => $permissions]);
    }

    /**
     * @Route("/map/items/{id}", name="map_items_show")
     */
    public function show($id){
      $item = $this->doctrine->getRepository(MapItem::class)->find($id);
      if (!$item) {
        // Just a shortcut for:
        // throw new NotFoundHttpException();
        throw $this->createNotFoundException('This map item does not exist.');
      }
      $itemType = $item->getItemType();

      $permissions = json_encode($this->service->getUserMapPermissions());
      return $this->render('map/map_item/show.html.twig', ['id' => $id, 'itemType' => $itemType, 'permissions' => $permissions]);
    }

    /**
     * @Route("/map/items/{id}/edit", name="map_items_edit")
     */
    public function edit($id){
      $item = $this->doctrine->getRepository(MapItem::class)->find($id);
      if (!$item) {
        throw $this->createNotFoundException('This map item does not exist.');
      }
      $itemType = $item->getItemType();

      $permissions = json_encode($this->service->getUserMapPermissions());
      return $this->render('map/map_item/edit.html.twig', ['id' => $id, 'itemType' => $itemType, 'permissions' => $permissions]);
    }
}
