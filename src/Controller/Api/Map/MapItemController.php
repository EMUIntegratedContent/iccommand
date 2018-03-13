<?php
namespace App\Controller\Api\Map;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\PersistentCollection;
use FOS\RestBundle\View\View;
use App\Entity\Map\MapItem;
use App\Entity\Map\MapBathroom;
use App\Entity\Map\MapBuilding;
use App\Entity\Map\MapEmergency;
use App\Entity\Map\MapEmergencyType;

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
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->findOneBy(['id' => $id]);
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
   * Get emergency types
   */
   public function getEmergencytypesAction()
   {
     $emergencyTypes = $this->getDoctrine()->getRepository(MapEmergencyType::class)->findAll();

     $serializer = $this->container->get('jms_serializer');
     $serialized = $serializer->serialize($emergencyTypes, 'json');
     $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

     return $response;
   }

  /**
   * Save a map item to the database
   */
  public function postMapitemAction(Request $request)
  {
    $itemType = $request->request->get('itemType');

    $serializer = $this->container->get('jms_serializer');

    $em = $this->getDoctrine()->getManager();

    switch($itemType){
      case "bathroom":
        $mapItem = new MapBathroom();
        $mapItem->setIsGenderNeutral($request->request->get('isGenderNeutral'));
        break;
      case "building":
        $mapItem = new MapBuilding();
        break;
      case "emergency":
        break;
      default:
        return new Response("This is not a valid map type.", 400, array('Content-Type' => 'application/json'));
    }

    // set common fields for all mapItem objects
    $mapItem->setName($request->request->get('name'));
    $mapItem->setDescription($request->request->get('description'));
    $mapItem->setLatitudeIllustration($request->request->get('latitudeIllustration'));
    $mapItem->setLongitudeIllustration($request->request->get('longitudeIllustration'));
    $mapItem->setLatitudeSatellite($request->request->get('latitudeSatellite'));
    $mapItem->setLongitudeStaellite($request->request->get('longitudeSatellite'));

    // validate map item
    $errors = $this->validate($mapItem);
    if (count($errors) > 0) {
        $serialized = $serializer->serialize($errors, 'json');
        $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));

        return $response;
    }
    // persist the map item
    $em->persist($mapItem);
    // Some things can only be done after a map item has been created
    switch($itemType){
      case "building":
        // Building Bathrooms
        foreach($request->request->get('bathrooms') as $bldgBathroom){
          $bathroom = new MapBathroom();
          $bathroom->setName($bldgBathroom['name']);
          $bathroom->setIsGenderNeutral($bldgBathroom['isGenderNeutral']);
          $bathroom->setBuilding($mapItem);

          $bathroomErrors = $this->validate($bathroom);
          if (count($bathroomErrors) > 0) {
              $serialized = $serializer->serialize($bathroomErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($bathroom); // persist but don't save until the end
        }

        // Building Emergency Devices
        foreach($request->request->get('emergencyDevices') as $bldgEmergency){
          // need to find the emergency type entity
          $emergencyType = $this->getDoctrine()->getRepository(MapEmergencyType::class)->find($bldgEmergency['type']['id']);
          if(!$emergencyType){
            $response = new Response("The emergency item type was not found.", 404, array('Content-Type' => 'application/json'));
          }
          $emergencyDevice = new MapEmergency();
          $emergencyDevice->setName($bldgEmergency['name']);
          $emergencyDevice->setType($emergencyType);
          $emergencyDevice->setBuilding($mapItem);

          $emergencyErrors = $this->validate($emergencyDevice);
          if (count($emergencyErrors) > 0) {
              $serialized = $serializer->serialize($emergencyErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($emergencyDevice); // persist but don't save until the end
        }
        break;
    }

    // commit everything to the database
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
    $serializer = $this->container->get('jms_serializer');

    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->find($request->request->get('id'));
    switch($itemType){
      case "bathroom":
        $mapItem->setIsGenderNeutral($request->request->get('isGenderNeutral'));
        break;
      case "building":
        // Building bathrooms
        foreach($request->request->get('bathrooms') as $bldgBathroom){
          // a new bathroom won't have an ID
          if(isset($bldgBathroom['id'])){
            $bathroom = $this->getDoctrine()->getRepository(MapItem::class)->find($bldgBathroom['id']);
          } else {
            $bathroom = new MapBathroom();
            $bathroom->setBuilding($mapItem);
          }
          $bathroom->setName($bldgBathroom['name']);
          $bathroom->setIsGenderNeutral($bldgBathroom['isGenderNeutral']);

          $bathroomErrors = $this->validate($bathroom);
          if (count($bathroomErrors) > 0) {
              $serialized = $serializer->serialize($bathroomErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($bathroom); // persist but don't save until the end
        }
        // Compare and delete any bathrooms not in the updated list
        $this->mapBuildingBathroomCompare($mapItem->getBathrooms(), $request->request->get('bathrooms'));

        // // Building Emergency Devices
        // foreach($request->request->get('emergencyDevices') as $bldgEmergency){
        //   // need to find the emergency type entity
        //   $emergencyType = $this->getDoctrine()->getRepository(MapEmergencyType::class)->find($bldgEmergency['type']['id']);
        //   if(!$emergencyType){
        //     $response = new Response("The emergency item type was not found.", 404, array('Content-Type' => 'application/json'));
        //   }
        //
        //   // a new device won't have an ID
        //   if(isset($bldgEmergency['id'])){
        //     $emergencyDevice = $this->getDoctrine()->getRepository(MapItem::class)->find($bldgEmergency['id']);
        //   } else {
        //     $emergencyDevice = new MapEmergency();
        //     $emergencyDevice->setBuilding($mapItem);
        //   }
        //   $emergencyDevice->setName($bldgEmergency['name']);
        //   $emergencyDevice->setType($emergencyType);
        //
        //   $emergencyErrors = $this->validate($emergencyDevice);
        //   if (count($emergencyErrors) > 0) {
        //       $serialized = $serializer->serialize($emergencyErrors, 'json');
        //       $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
        //       return $response;
        //   }
        //   $em->persist($emergencyDevice); // persist but don't save until the end
        // }
        // // Compare and delete any emergency devices not in the updated list
        // $this->mapBuildingEmergencyCompare($mapItem->getEmergencyDevices(), $request->request->get('emergencyDevices'));

        break;
      default:
        return new Response("This is not a valid map type.", 400, array('Content-Type' => 'application/json'));
    }

    // set common fields for all mapItem objects
    $mapItem->setName($request->request->get('name'));
    $mapItem->setDescription($request->request->get('description'));
    $mapItem->setLatitudeIllustration($request->request->get('latitudeIllustration'));
    $mapItem->setLongitudeIllustration($request->request->get('longitudeIllustration'));
    $mapItem->setLatitudeSatellite($request->request->get('latitudeSatellite'));
    $mapItem->setLongitudeStaellite($request->request->get('longitudeSatellite'));

    // validate map item
    $errors = $this->validate($mapItem);
    if (count($errors) > 0) {
        $serialized = $serializer->serialize($errors, 'json');
        $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
        return $response;
    }

    // save the map item
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

  // NEEDS TO GO INTO A SERVICE
  protected function validate($mapItem){
    $validator = $this->container->get('validator');
    $errors = $validator->validate($mapItem);

    return $errors;
  }

  // NEEDS TO GO INTO A SERVICE
  protected function mapBuildingBathroomCompare(PersistentCollection $currentBathrooms, array $updatedBathrooms): bool
  {
    $current_ids = array();
    foreach($currentBathrooms as $currentBathroom){
      $current_ids[] = $currentBathroom->getId();
    }
    $updated_ids = array();
    foreach($updatedBathrooms as $updatedBathroom){
      // new bathrooms will NOT have an id
      if(isset($updatedBathroom['id'])){
        $updated_ids[] = $updatedBathroom['id'];
      }
    }
    $bathroomsToDelete = array_diff($current_ids, $updated_ids); // result is the items that do NOT appear in the updated IDs

    // Find and remove any items that do NOT appear in the updated IDs
    foreach($bathroomsToDelete as $btd){
      $bathroom = $this->getDoctrine()->getRepository(MapItem::class)->find($btd);
      $this->deleteMapItem($bathroom);
    }
    return true;
  }

  // NEEDS TO GO INTO A SERVICE
  protected function deleteMapItem(MapItem $mapItem): bool{
    $em = $this->getDoctrine()->getManager();
    $em->remove($mapItem);
    $em->flush();

    return true;
  }
}
