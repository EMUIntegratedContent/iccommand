<?php
namespace App\Controller\Api\Map;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use App\Entity\MapItem;
use App\Entity\MapBathroom;

class MapItemController extends FOSRestController{

  public function getMapItemsAction()
  {
    $items = $this->getDoctrine()->getRepository(MapItem::class)->findAll();

    $serializer = $this->container->get('serializer');
    $serialized = $serializer->serialize($items, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  public function postMapItemAction(Request $request)
  {
    $type = $request->request->get('itemType');

    switch($type){
      case "bathroom":
        $mapItem = new MapBathroom();
        $mapItem->setIsGenderNeutral($request->request->get('isGenderNeutral'));
        break;
      default:
        return new Response("This is not a valid map type.", 400, array('Content-Type' => 'application/json'));
    }

    // set common fields for all mapItem objects
    $mapItem->setName($request->request->get('name'));
    $mapItem->setSlug($request->request->get('slug'));
    $mapItem->setDescription($request->request->get('description'));
    $mapItem->setLatitudeIllustration($request->request->get('latitudeIllustration'));
    $mapItem->setLongitudeIllustration($request->request->get('longitudeIllustration'));
    $mapItem->setLatitudeSatellite($request->request->get('latitudeStatellite'));
    $mapItem->setLongitudeStaellite($request->request->get('longitudeSatellite'));

    $serializer = $this->container->get('serializer');

    $validator = $this->container->get('validator');
    $errors = $validator->validate($mapItem);
    if (count($errors) > 0) {
        /*
         * Uses a __toString method on the $errors variable which is a
         * ConstraintViolationList object. This gives us a nice string
         * for debugging.
         */
        $errorsString = (string) $errors;
        $serialized = $serializer->serialize($errors, 'json');

        $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));

        return $response;
    }



    // save the map item
    $em = $this->getDoctrine()->getManager();
    $em->persist($mapItem);
    $em->flush();

    $serialized = $serializer->serialize($mapItem, 'json');
    $response = new Response($serialized, 201, array('Content-Type' => 'application/json'));
    return $response;
  }
}
