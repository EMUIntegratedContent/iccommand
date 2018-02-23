<?php
namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use App\Entity\MapItem;

class MapBathroomController extends FOSRestController{

  public function getMapItemsAction()
  {
    $items = $this->getDoctrine()->getRepository(MapItem::class)->findAll();

    $serializer = $this->container->get('serializer');
    $serialized = $serializer->serialize($items, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }
}
