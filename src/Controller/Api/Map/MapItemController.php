<?php
namespace App\Controller\Api\Map;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use App\Entity\Map\MapItem;
use App\Entity\Map\MapBathroom;

class MapItemController extends FOSRestController{

  /**
   * Get all map items
   */
  public function getMapitemsAction()
  {
    $mapItems = $this->getDoctrine()->getRepository(MapItem::class)->findAll();

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($mapItems, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Get a single map item
   */
  public function getMapitemAction($id){
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->findOneBy(['id' => $id, 'deleted' => null]);
    if(!$mapItem){
      $response = new Response("The map item you requested was not found.", 404, array('Content-Type' => 'application/json'));
      return $response;
    }
    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($mapItem, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Save a map item to the database
   */
  public function postMapitemAction(Request $request)
  {
    $itemType = $request->request->get('itemType');

    switch($itemType){
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
    $mapItem->setLatitudeSatellite($request->request->get('latitudeSatellite'));
    $mapItem->setLongitudeStaellite($request->request->get('longitudeSatellite'));

    $serializer = $this->container->get('jms_serializer');

    // validate map item
    $errors = $this->validate($mapItem);
    if (count($errors) > 0) {
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

  /**
   * Update a map item to the database
   */
  public function putMapitemAction(Request $request)
  {
    $itemType = $request->request->get('itemType');

    $em = $this->getDoctrine()->getManager();
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->find($request->request->get('id'));

    switch($itemType){
      case "bathroom":
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
    $mapItem->setLatitudeSatellite($request->request->get('latitudeSatellite'));
    $mapItem->setLongitudeStaellite($request->request->get('longitudeSatellite'));

    $serializer = $this->container->get('jms_serializer');

    // validate map item
    $errors = $this->validate($mapItem);
    if (count($errors) > 0) {
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

  /**
   * Delete a map item from the database
   */
  public function deleteMapitemAction($id)
  {
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->find($id);

    $em = $this->getDoctrine()->getManager();
    $em->remove($mapItem);
    $em->flush();

    $response = new Response('Item has been deleted.', 204, array('Content-Type' => 'application/json'));
    return $response;
  }

  protected function validate($mapItem){
    $validator = $this->container->get('validator');
    $errors = $validator->validate($mapItem);

    return $errors;
  }
}
