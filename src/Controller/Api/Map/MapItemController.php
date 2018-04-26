<?php
namespace App\Controller\Api\Map;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\PersistentCollection;
use Hateoas\HateoasBuilder;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\View\View;
use App\Entity\Map\MapItem;
use App\Entity\Map\MapBathroom;
use App\Entity\Map\MapBuilding;
use App\Entity\Map\MapBuildingType;
use App\Entity\Map\MapBus;
use App\Entity\Map\MapDining;
use App\Entity\Map\MapEmergency;
use App\Entity\Map\MapEmergencyType;
use App\Entity\Map\MapExhibit;
use App\Entity\Map\MapExhibitType;
use App\Entity\Map\MapParking;
use App\Entity\Map\MapParkingType;
use App\Service\MapItemService;

class MapItemController extends FOSRestController{

  private $service;

  public function __construct(MapItemService $service){
    $this->service = $service;
  }

  public function getExternalMapitemsAction(){
    $hateoas = HateoasBuilder::create()->build();
    $mapItems = $this->getDoctrine()->getRepository(MapItem::class)->findBy([],['name' => 'asc']);
    $serialized = $hateoas->serialize($mapItems, 'json');

    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));
    return $response;
  }

  /**
   * Get all map items
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_VIEW')")
   */
  public function getMapitemsAction() : Response
  {
    $mapItems = $this->getDoctrine()->getRepository(MapItem::class)->findBy([],['name' => 'asc']);

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($mapItems, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Get a single map item
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_VIEW')")
   */
  public function getMapitemAction($id) : Response
  {
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->findOneBy(['id' => $id]);
    if(!$mapItem){
      $response = new Response("The map item you requested was not found.", 404, array('Content-Type' => 'application/json'));
      return $response;
    }

    // Need to return NULL fields too (latitude and longitude don't have to have a value)
    // TUTORIAL: https://stackoverflow.com/questions/16784996/how-to-show-null-value-in-json-in-fos-rest-bundle-with-jms-serializer
    $context = new SerializationContext();
    $context->setSerializeNull(true);

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($mapItem, 'json', $context);
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Get buildings
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_VIEW')")
   */
   public function getMapbuildingsAction()
   {
     $em = $this->getDoctrine()->getManager();
     $itemRepo = $em->getRepository('App\Entity\Map\MapBuilding');
     $buildings = $itemRepo->findAllBuildingsWithFields(['b.id', 'b.name']); // don't need the whole MapBuilding object, so just grab ID and name from the repo method

     $serializer = $this->container->get('jms_serializer');
     $serialized = $serializer->serialize($buildings, 'json');
     $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

     return $response;
   }

   /**
    * Get building types
    *
    * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_VIEW')")
    */
    public function getMapbuildingtypesAction()
    {
      $em = $this->getDoctrine()->getManager();
      $itemRepo = $em->getRepository('App\Entity\Map\MapBuildingType');
      $buildingsTypes = $itemRepo->findAllBuildingTypesWithFields(['bt.id', 'bt.name']); // don't need the whole MapBuildingType object, so just grab ID and name from the repo method

      $serializer = $this->container->get('jms_serializer');
      $serialized = $serializer->serialize($buildingsTypes, 'json');
      $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

      return $response;
    }

  /**
   * Get emergency types
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_VIEW')")
   */
   public function getMapemergencytypesAction()
   {
     $emergencyTypes = $this->getDoctrine()->getRepository(MapEmergencyType::class)->findAll();

     $serializer = $this->container->get('jms_serializer');
     $serialized = $serializer->serialize($emergencyTypes, 'json');
     $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

     return $response;
   }

   /**
    * Get emergency types
    *
    * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_VIEW')")
    */
    public function getMapexhibittypesAction() : Response
    {
      $exhibitTypes = $this->getDoctrine()->getRepository(MapExhibitType::class)->findAll();

      $serializer = $this->container->get('jms_serializer');
      $serialized = $serializer->serialize($exhibitTypes, 'json');
      $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

      return $response;
    }

  /**
   * Get parking types
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_VIEW')")
   */
   public function getMapparkingtypesAction() : Response
   {
     $parkingTypes = $this->getDoctrine()->getRepository(MapParkingType::class)->findAll();

     $serializer = $this->container->get('jms_serializer');
     $serialized = $serializer->serialize($parkingTypes, 'json');
     $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

     return $response;
   }

  /**
   * Save a map item to the database
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_CREATE')")
   */
  public function postMapitemAction(Request $request) : Response
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
        $mapItem->setHours($request->request->get('hours'));
        $mapItem->setAddress($request->request->get('address'));
        // Building Type
        $buildingType = $this->getDoctrine()->getRepository(MapBuildingType::class)->find($request->request->get('buildingType')['id']);
        if($buildingType){
          $mapItem->setBuildingType($buildingType);
        }
        break;
      case "bus":
        $mapItem = new MapBus();
        break;
      case "emergency device":
        $mapItem = new MapEmergency();
        break;
      case "exhibit":
        $mapItem = new MapExhibit();
        break;
      case "parking":
        $mapItem = new MapParking();
        $mapItem->setSpaces($request->request->get('spaces'));
        $mapItem->setHours($request->request->get('hours'));
        $mapItem->setHasHandicapSpaces($request->request->get('hasHandicapSpaces'));
        break;
      default:
        return new Response("This map item type cannot be created here.", 400, array('Content-Type' => 'application/json'));
    }

    // set common fields for all mapItem objects
    $mapItem->setName($request->request->get('name'));
    $mapItem->setDescription($request->request->get('description'));
    $mapItem->setLatitudeIllustration($request->request->get('latitudeIllustration'));
    $mapItem->setLongitudeIllustration($request->request->get('longitudeIllustration'));
    $mapItem->setLatitudeSatellite($request->request->get('latitudeSatellite'));
    $mapItem->setLongitudeStaellite($request->request->get('longitudeSatellite'));

    // validate map item
    $errors = $this->service->validate($mapItem);
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

          $bathroomErrors = $this->service->validate($bathroom);
          if (count($bathroomErrors) > 0) {
              $serialized = $serializer->serialize($bathroomErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($bathroom); // persist but don't save until the end
        }

        // Building Dining Options
        foreach($request->request->get('diningOptions') as $bldgDining){
          $dining = new MapDining();
          $dining->setName($bldgDining['name']);
          $dining->setHours($bldgDining['hours']);
          $dining->setDescription($bldgDining['description']);
          $dining->setBuilding($mapItem);

          $diningErrors = $this->service->validate($dining);
          if (count($diningErrors) > 0) {
              $serialized = $serializer->serialize($diningErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($dining); // persist but don't save until the end
        }

        // Building Emergency Devices
        foreach($request->request->get('emergencyDevices') as $bldgEmergency){
          // need to find the emergency type entity
          $emergencyType = $this->getDoctrine()->getRepository(MapEmergencyType::class)->find($bldgEmergency['type']['id']);
          if(!$emergencyType){
            $response = new Response("The emergency item type was not found.", 404, array('Content-Type' => 'application/json'));
            return $response;
          }
          $emergencyDevice = new MapEmergency();
          $emergencyDevice->setName($bldgEmergency['name']);
          $emergencyDevice->setType($emergencyType);
          $emergencyDevice->setBuilding($mapItem);

          $emergencyErrors = $this->service->validate($emergencyDevice);
          if (count($emergencyErrors) > 0) {
              $serialized = $serializer->serialize($emergencyErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($emergencyDevice); // persist but don't save until the end
        }

        // Building Exhibits
        foreach($request->request->get('exhibits') as $bldgExhibit){
          // need to find the exhibit type entity
          $exhibitType = $this->getDoctrine()->getRepository(MapExhibitType::class)->find($bldgExhibit['type']['id']);
          if(!$exhibitType){
            $response = new Response("The exhibit type was not found.", 404, array('Content-Type' => 'application/json'));
            return $response;
          }
          $exhibit = new MapExhibit();
          $exhibit->setName($bldgExhibit['name']);
          $exhibit->setDescription($bldgExhibit['description']);
          $exhibit->setType($exhibitType);
          $exhibit->setBuilding($mapItem);

          $exibitErrors = $this->service->validate($exhibit);
          if (count($exibitErrors) > 0) {
              $serialized = $serializer->serialize($exibitErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($exhibit); // persist but don't save until the end
        }
        break;
      case "emergency device":
      case "exhibit":
        // Find the building in which this device/exhibit is located (if any)
        $building = $request->request->get('building');
        if($building){
          $building = $this->getDoctrine()->getRepository(MapItem::class)->find($building['id']);
        }
        $mapItem->setBuilding($building);

        // Get which type of device/exhibit this is
        $type = $request->request->get('type');
        if(!$type){
          return new Response("Each ". $itemType . " must have a type set.", 400, array('Content-Type' => 'application/json'));
        }
        if($itemType == 'emergency device'){
          $type = $this->getDoctrine()->getRepository(MapEmergencyType::class)->find($type['id']);
        } else {
          $type = $this->getDoctrine()->getRepository(MapExhibitType::class)->find($type['id']);
        }
        $mapItem->setType($type);
        break;
      case "parking":
        // Parking lot types
        foreach($request->request->get('parkingTypes') as $type){
          // find the parking type entity
          $parkingType = $this->getDoctrine()->getRepository(MapParkingType::class)->find($type['id']);
          if(!$parkingType){
            $response = new Response("The parking lot type was not found.", 404, array('Content-Type' => 'application/json'));
            return $response;
          }
          $mapItem->addParkingType($parkingType);
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
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_EDIT')")
   */
  public function putMapitemAction(Request $request) : Response
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
        $mapItem->setHours($request->request->get('hours'));
        $mapItem->setAddress($request->request->get('address'));
        // Building Type
        $buildingType = $this->getDoctrine()->getRepository(MapBuildingType::class)->find($request->request->get('buildingType')['id']);
        if($buildingType){
          $mapItem->setBuildingType($buildingType);
        }

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

          $bathroomErrors = $this->service->validate($bathroom);
          if (count($bathroomErrors) > 0) {
              $serialized = $serializer->serialize($bathroomErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($bathroom); // persist but don't save until the end
        }
        // Compare and delete any bathrooms not in the updated list
        $this->service->mapItemCollectionCompare($mapItem->getBathrooms(), $request->request->get('bathrooms'));

        // Building Dining Options
        foreach($request->request->get('diningOptions') as $bldgDining){
          // a new dining option won't have an ID
          if(isset($bldgDining['id'])){
            $dining = $this->getDoctrine()->getRepository(MapItem::class)->find($bldgDining['id']);
          } else {
            $dining = new MapDining();
            $dining->setBuilding($mapItem);
          }
          $dining->setName($bldgDining['name']);
          $dining->setDescription($bldgDining['description']);
          $dining->setHours($bldgDining['hours']);

          $diningErrors = $this->service->validate($dining);
          if (count($diningErrors) > 0) {
              $serialized = $serializer->serialize($diningErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($dining); // persist but don't save until the end
        }
        // Compare and delete any bathrooms not in the updated list
        $this->service->mapItemCollectionCompare($mapItem->getDiningOptions(), $request->request->get('diningOptions'));

        // Building Emergency Devices
        foreach($request->request->get('emergencyDevices') as $bldgEmergency){
          // need to find the emergency type entity
          $emergencyType = $this->getDoctrine()->getRepository(MapEmergencyType::class)->find($bldgEmergency['type']['id']);
          if(!$emergencyType){
            $response = new Response("The emergency item type was not found.", 404, array('Content-Type' => 'application/json'));
            return $response;
          }

          // a new device won't have an ID
          if(isset($bldgEmergency['id'])){
            $emergencyDevice = $this->getDoctrine()->getRepository(MapItem::class)->find($bldgEmergency['id']);
          } else {
            $emergencyDevice = new MapEmergency();
            $emergencyDevice->setBuilding($mapItem);
          }
          $emergencyDevice->setName($bldgEmergency['name']);
          $emergencyDevice->setType($emergencyType);

          $emergencyErrors = $this->service->validate($emergencyDevice);
          if (count($emergencyErrors) > 0) {
              $serialized = $serializer->serialize($emergencyErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($emergencyDevice); // persist but don't save until the end
        }
        // Compare and delete any emergency devices not in the updated list
        $this->service->mapItemCollectionCompare($mapItem->getEmergencyDevices(), $request->request->get('emergencyDevices'));

        // Building Exhibits
        foreach($request->request->get('exhibits') as $bldgExhibit){
          // need to find the exhibit type entity
          $exhibitType = $this->getDoctrine()->getRepository(MapExhibitType::class)->find($bldgExhibit['type']['id']);
          if(!$exhibitType){
            $response = new Response("The exhibit type was not found.", 404, array('Content-Type' => 'application/json'));
            return $response;
          }
          // a new exhibit won't have an ID
          if(isset($bldgExhibit['id'])){
            $exhibit = $this->getDoctrine()->getRepository(MapItem::class)->find($bldgExhibit['id']);
          } else {
            $exhibit = new MapExhibit();
            $exhibit->setBuilding($mapItem);
          }
          $exhibit->setName($bldgExhibit['name']);
          $exhibit->setDescription($bldgExhibit['description']);
          $exhibit->setType($exhibitType);

          $exhibitErrors = $this->service->validate($exhibit);
          if (count($exhibitErrors) > 0) {
              $serialized = $serializer->serialize($exhibitErrors, 'json');
              $response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
              return $response;
          }
          $em->persist($exhibit); // persist but don't save until the end
        }
        // Compare and delete any emergency devices not in the updated list
        $this->service->mapItemCollectionCompare($mapItem->getExhibits(), $request->request->get('exhibits'));
        break;
      case "bus":
        break;
      case "emergency device":
      case "exhibit":
        // Find the building in which this device/exhibit is located (if any)
        $building = $request->request->get('building');
        if($building){
          $building = $this->getDoctrine()->getRepository(MapItem::class)->find($building['id']);
        }
        $mapItem->setBuilding($building);

        // Get which type of device/exhibit this is
        $type = $request->request->get('type');
        if(!$type){
          return new Response("Each ". $itemType . " must have a type set.", 400, array('Content-Type' => 'application/json'));
        }
        if($itemType == 'emergency device'){
          $type = $this->getDoctrine()->getRepository(MapEmergencyType::class)->find($type['id']);
        } else {
          $type = $this->getDoctrine()->getRepository(MapExhibitType::class)->find($type['id']);
        }
        $mapItem->setType($type);
        break;
      case "parking":
        $mapItem->setSpaces($request->request->get('spaces'));
        $mapItem->setHours($request->request->get('hours'));
        $mapItem->setHasHandicapSpaces($request->request->get('hasHandicapSpaces'));
        // Compare and delete any parking lot types not in the updated list
        $this->service->mapParkingLotTypeCompare($mapItem->getParkingTypes(), $request->request->get('parkingTypes'), $mapItem);
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
    $errors = $this->service->validate($mapItem);
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
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_DELETE')")
   */
  public function deleteMapitemAction($id) : Response
  {
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->find($id);

    $em = $this->getDoctrine()->getManager();
    $em->remove($mapItem);
    $em->flush();

    $response = new Response('Item has been deleted.', 204, array('Content-Type' => 'application/json'));
    return $response;
  }
}
