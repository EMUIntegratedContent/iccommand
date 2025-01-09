<?php

namespace App\Controller\Api\Map;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Serializer\SerializerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
use App\Entity\Map\MapService;
use App\Entity\Map\MapServiceType;
use App\Service\MapItemService;
use App\Entity\Map\MapDispenser;
use Symfony\Component\ExpressionLanguage\Expression;

class MapItemController extends AbstractFOSRestController{
	private MapItemService $service;
	private ManagerRegistry $doctrine;
	private SerializerInterface $serializer;

	public function __construct(MapItemService $service, ManagerRegistry $doctrine, SerializerInterface $serializer){
		$this->service = $service;
		$this->doctrine = $doctrine;
		$this->serializer = $serializer;
	}

	/**
	 * EXTERNAL API ENDPOINT. Get all map items
	 */
	#[Rest\Get(path: "external/mapitems")]
	public function getExternalMapitemsAction(): Response{
		$mapItems = $this->doctrine->getRepository(MapItem::class)->findBy([], ['name' => 'asc']);

		$serialized = $this->serializer->serialize($mapItems, 'json', ['groups' => 'bldgs']);
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get all map items
	 */
	#[Rest\Get(path: "mapitems")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapitemsAction(): Response{
		$mapItems = $this->doctrine->getRepository(MapItem::class)->findBy([], ['name' => 'asc']);

		$serialized = $this->serializer->serialize($mapItems, 'json', ['groups' => ['bldgs']]);
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get a single map item
	 */
	#[Rest\Get(path: "mapitems/{id}")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapitemAction($id): Response{
		$mapItem = $this->doctrine->getRepository(MapItem::class)->findOneBy(['id' => $id]);
		if(!$mapItem){
			return new Response("The map item you requested was not found.", 404, ['Content-Type' => 'application/json']);
		}

		$serialized = $this->serializer->serialize($mapItem, 'json', ['groups' => ['bldgs']]);

		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get buildings
	 */
	#[Rest\Get(path: "mapbuildings/")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapbuildingsAction(): Response{
		$itemRepo = $this->doctrine->getRepository(MapBuilding::class);
		$buildings = $itemRepo->findAllBuildingsWithFields(['b.id', 'b.name']); // don't need the whole MapBuilding object, so just grab ID and name from the repo method

		$serialized = $this->serializer->serialize($buildings, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get building types
	 */
	#[Rest\Get(path: "mapbuildingtypes/")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapbuildingtypesAction(): Response{
		$itemRepo = $this->doctrine->getRepository(MapBuildingType::class);
		$buildingsTypes = $itemRepo->findAllBuildingTypesWithFields(['bt.id', 'bt.name']); // don't need the whole MapBuildingType object, so just grab ID and name from the repo method

		$serialized = $this->serializer->serialize($buildingsTypes, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get emergency types
	 */
	#[Rest\Get(path: "mapemergencytypes/")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapemergencytypesAction(): Response{
		$emergencyTypes = $this->doctrine->getRepository(MapEmergencyType::class)->findAll();

		$serialized = $this->serializer->serialize($emergencyTypes, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get exhibit types
	 */
	#[Rest\Get(path: "mapexhibittypes/")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapexhibittypesAction(): Response{
		$exhibitTypes = $this->doctrine->getRepository(MapExhibitType::class)->findAll();

		$serialized = $this->serializer->serialize($exhibitTypes, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get parking types
	 */
	#[Rest\Get(path: "mapparkingtypes/")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapparkingtypesAction(): Response{
		$parkingTypes = $this->doctrine->getRepository(MapParkingType::class)->findAll();

		$serialized = $this->serializer->serialize($parkingTypes, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Get service types
	 */
	#[Rest\Get(path: "mapservicetypes/")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_VIEW")'))]
	public function getMapservicetypesAction(): Response{
		$serviceTypes = $this->doctrine->getRepository(MapServiceType::class)->findAll();

		$serialized = $this->serializer->serialize($serviceTypes, 'json');
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Save a map item to the database
	 */
	#[Rest\Post(path: "mapitems")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_CREATE")'))]
	public function postMapitemAction(Request $request): Response{
		$itemType = $request->request->get('itemType');

		$em = $this->doctrine->getManager();

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
				$buildingType = $this->doctrine->getRepository(MapBuildingType::class)->find($request->get('buildingType')['id']);
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
			case "service":
				$mapItem = new MapService();
				break;
			default:
				return new Response("Unable to create an item with this type.", 400, ['Content-Type' => 'application/json']);
		}

		// set common fields for all mapItem objects
		$mapItem->setName($request->request->get('name'));
		$mapItem->setDescription($request->request->get('description'));
		$mapItem->setLatitudeIllustration($request->request->get('latitudeIllustration'));
		$mapItem->setLongitudeIllustration($request->request->get('longitudeIllustration'));
		$mapItem->setLatitudeSatellite($request->request->get('latitudeSatellite'));
		$mapItem->setLongitudeStaellite($request->request->get('longitudeSatellite'));
		$mapItem->setAdmissionsTour($request->request->get('admissionsTour'));
		if($request->request->get('alias') != ''){
			$mapItem->setAlias($request->request->get('alias'));
		}

		// validate map item
		$errors = $this->service->validate($mapItem);
		if(count($errors) > 0){
			$serialized = $this->serializer->serialize($errors, 'json');
			return new Response($serialized, 422, ['Content-Type' => 'application/json']);
		}
		// persist the map item
		$em->persist($mapItem);

		// Some things can only be done after a map item has been created
		switch($itemType){
			case "building":
				// Building Bathrooms
				foreach($request->get('bathrooms') as $bldgBathroom){
					$bathroom = new MapBathroom();
					$bathroom->setName($bldgBathroom['name']);
					$bathroom->setIsGenderNeutral($bldgBathroom['isGenderNeutral']);
					$bathroom->setBuilding($mapItem);

					$bathroomErrors = $this->service->validate($bathroom);
					if(count($bathroomErrors) > 0){
						$serialized = $this->serializer->serialize($bathroomErrors, 'json');
						return new Response($serialized, 422, ['Content-Type' => 'application/json']);
					}
					$em->persist($bathroom); // persist but don't save until the end
				}

				// Building Dining Options
				foreach($request->get('diningOptions') as $bldgDining){
					$dining = new MapDining();
					$dining->setName($bldgDining['name']);
					$dining->setHours($bldgDining['hours']);
					$dining->setDescription($bldgDining['description']);
					$dining->setBuilding($mapItem);

					$diningErrors = $this->service->validate($dining);
					if(count($diningErrors) > 0){
						$serialized = $this->serializer->serialize($diningErrors, 'json');
						return new Response($serialized, 422, ['Content-Type' => 'application/json']);
					}
					$em->persist($dining); // persist but don't save until the end
				}

				// Building Emergency Devices
				foreach($request->get('emergencyDevices') as $bldgEmergency){
					// need to find the emergency type entity
					$emergencyType = $this->doctrine->getRepository(MapEmergencyType::class)->find($bldgEmergency['type']['id']);
					if(!$emergencyType){
						return new Response("The emergency item type was not found.", 404, ['Content-Type' => 'application/json']);
					}
					$emergencyDevice = new MapEmergency();
					$emergencyDevice->setName($bldgEmergency['name']);
					$emergencyDevice->setType($emergencyType);
					$emergencyDevice->setBuilding($mapItem);

					$emergencyErrors = $this->service->validate($emergencyDevice);
					if(count($emergencyErrors) > 0){
						$serialized = $this->serializer->serialize($emergencyErrors, 'json');
						return new Response($serialized, 422, ['Content-Type' => 'application/json']);
					}
					$em->persist($emergencyDevice); // persist but don't save until the end
				}

				// Building Exhibits
				foreach($request->get('exhibits') as $bldgExhibit){
					// need to find the exhibit type entity
					$exhibitType = $this->doctrine->getRepository(MapExhibitType::class)->find($bldgExhibit['type']['id']);
					if(!$exhibitType){
						return new Response("The exhibit type was not found.", 404, ['Content-Type' => 'application/json']);
					}
					$exhibit = new MapExhibit();
					$exhibit->setName($bldgExhibit['name']);
					$exhibit->setDescription($bldgExhibit['description']);
					$exhibit->setType($exhibitType);
					$exhibit->setBuilding($mapItem);

					$exibitErrors = $this->service->validate($exhibit);
					if(count($exibitErrors) > 0){
						$serialized = $this->serializer->serialize($exibitErrors, 'json');
						return new Response($serialized, 422, ['Content-Type' => 'application/json']);
					}
					$em->persist($exhibit); // persist but don't save until the end
				}

				// Building Services
				foreach($request->get('services') as $bldgService){
					// need to find the service type entity
					$serviceType = $this->doctrine->getRepository(MapServiceType::class)->find($bldgService['type']['id']);
					if(!$serviceType){
						return new Response("The service type was not found.", 404, ['Content-Type' => 'application/json']);
					}
					$service = new MapService();
					$service->setName($bldgService['name']);
					$service->setDescription($bldgService['description']);
					$service->setType($serviceType);
					$service->setBuilding($mapItem);

					$serviceErrors = $this->service->validate($service);
					if(count($serviceErrors) > 0){
						$serialized = $this->serializer->serialize($serviceErrors, 'json');
						return new Response($serialized, 422, ['Content-Type' => 'application/json']);
					}
					$em->persist($service); // persist but don't save until the end
				}
				break;
			case "emergency device":
			case "exhibit":
				// Find the building in which this device/exhibit is located (if any)
				$building = $request->get('building');
				if($building){
					$building = $this->doctrine->getRepository(MapItem::class)->find($building['id']);
				}
				$mapItem->setBuilding($building);

				// Get which type of device/exhibit this is
				$type = $request->get('type');
				if(!$type){
					return new Response("Each ".$itemType." must have a type set.", 400, ['Content-Type' => 'application/json']);
				}
				if($itemType == 'emergency device'){
					$type = $this->doctrine->getRepository(MapEmergencyType::class)->find($type['id']);
				}
				else{
					$type = $this->doctrine->getRepository(MapExhibitType::class)->find($type['id']);
				}
				$mapItem->setType($type);
				break;
			case "parking":
				// Parking lot types
				foreach($request->get('parkingTypes') as $type){
					// find the parking type entity
					$parkingType = $this->doctrine->getRepository(MapParkingType::class)->find($type['id']);
					if(!$parkingType){
						return new Response("The parking lot type was not found.", 404, ['Content-Type' => 'application/json']);
					}
					$mapItem->addParkingType($parkingType);
				}
				break;
			case "service":
				// Find the building in which this device/exhibit is located (if any)
				$building = $request->request->get('building');
				if($building){
					$building = $this->doctrine->getRepository(MapItem::class)->find($building['id']);
				}
				$mapItem->setBuilding($building);

				// Get which type of device/exhibit this is
				$type = $request->request->get('type');
				if(!$type){
					return new Response("Each ".$itemType." must have a type set.", 400, ['Content-Type' => 'application/json']);
				}
				$mapItem->setType($type);
				break;
		}

		// commit everything to the database
		$em->flush();

		$serialized = $this->serializer->serialize($mapItem, 'json', ['groups' => ['bldgs']]);
		return new Response($serialized, 201, ['Content-Type' => 'application/json']);
	}

	/**
	 * Update a map item to the database
	 */
	#[Rest\Put(path: "mapitem")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_EDIT")'))]
	public function putMapitemAction(Request $request): Response{
		$itemType = $request->request->get('itemType');

		$em = $this->doctrine->getManager();

		$mapItem = $this->doctrine->getRepository(MapItem::class)->find($request->request->get('id'));
		switch($itemType){
			case "bathroom":
				$mapItem->setIsGenderNeutral($request->request->get('isGenderNeutral'));
				break;
			case "building":
				$mapItem->setHours($request->request->get('hours'));
				$mapItem->setAddress($request->request->get('address'));
				// Building Type
				$buildingType = $this->doctrine->getRepository(MapBuildingType::class)->find($request->get('buildingType')['id']);
				if($buildingType){
					$mapItem->setBuildingType($buildingType);
				}

				// Building bathrooms
				foreach($request->get('bathrooms') as $bldgBathroom){
					// a new bathroom won't have an ID
					if(isset($bldgBathroom['id'])){
						$bathroom = $this->doctrine->getRepository(MapItem::class)->find($bldgBathroom['id']);
					}
					else{
						$bathroom = new MapBathroom();
						$bathroom->setBuilding($mapItem);
					}
					$bathroom->setName($bldgBathroom['name']);
					$bathroom->setIsGenderNeutral($bldgBathroom['isGenderNeutral']);
					$bathroom->setAdmissionsTour(0);

					$bathroomErrors = $this->service->validate($bathroom);
					if(count($bathroomErrors) > 0){
						$serialized = $this->serializer->serialize($bathroomErrors, 'json');
						return new Response($serialized, 422, array('Content-Type' => 'application/json'));
					}
					$em->persist($bathroom); // persist but don't save until the end
				}
				// Compare and delete any bathrooms not in the updated list
				$this->service->mapItemCollectionCompare($mapItem->getBathrooms(), $request->get('bathrooms'));

				// Building Dining Options
				foreach($request->get('diningOptions') as $bldgDining){
					// a new dining option won't have an ID
					if(isset($bldgDining['id'])){
						$dining = $this->doctrine->getRepository(MapItem::class)->find($bldgDining['id']);
					}
					else{
						$dining = new MapDining();
						$dining->setBuilding($mapItem);
					}
					$dining->setName($bldgDining['name']);
					$dining->setDescription($bldgDining['description']);
					$dining->setHours($bldgDining['hours']);
					$dining->setAdmissionsTour(0);

					$diningErrors = $this->service->validate($dining);
					if(count($diningErrors) > 0){
						$serialized = $this->serializer->serialize($diningErrors, 'json');
						$response = new Response($serialized, 422, array('Content-Type' => 'application/json'));
						return $response;
					}
					$em->persist($dining); // persist but don't save until the end
				}
				// Compare and delete any bathrooms not in the updated list
				$this->service->mapItemCollectionCompare($mapItem->getDiningOptions(), $request->get('diningOptions'));

				// Building Emergency Devices
				foreach($request->get('emergencyDevices') as $bldgEmergency){
					// need to find the emergency type entity
					$emergencyType = $this->doctrine->getRepository(MapEmergencyType::class)->find($bldgEmergency['type']['id']);
					if(!$emergencyType){
						return new Response("The emergency item type was not found.", 404, array('Content-Type' => 'application/json'));
					}

					// a new device won't have an ID
					if(isset($bldgEmergency['id'])){
						$emergencyDevice = $this->doctrine->getRepository(MapItem::class)->find($bldgEmergency['id']);
					}
					else{
						$emergencyDevice = new MapEmergency();
						$emergencyDevice->setBuilding($mapItem);
					}
					$emergencyDevice->setName($bldgEmergency['name']);
					$emergencyDevice->setType($emergencyType);
					$emergencyDevice->setAdmissionsTour(0);

					$emergencyErrors = $this->service->validate($emergencyDevice);
					if(count($emergencyErrors) > 0){
						$serialized = $this->serializer->serialize($emergencyErrors, 'json');
						return new Response($serialized, 422, array('Content-Type' => 'application/json'));
					}
					$em->persist($emergencyDevice); // persist but don't save until the end
				}
				// Compare and delete any emergency devices not in the updated list
				$this->service->mapItemCollectionCompare($mapItem->getEmergencyDevices(), $request->get('emergencyDevices'));

				// Building Exhibits
				foreach($request->get('exhibits') as $bldgExhibit){
					// need to find the exhibit type entity
					$exhibitType = $this->doctrine->getRepository(MapExhibitType::class)->find($bldgExhibit['type']['id']);
					if(!$exhibitType){
						return new Response("The exhibit type was not found.", 404, array('Content-Type' => 'application/json'));
					}
					// a new exhibit won't have an ID
					if(isset($bldgExhibit['id'])){
						$exhibit = $this->doctrine->getRepository(MapItem::class)->find($bldgExhibit['id']);
					}
					else{
						$exhibit = new MapExhibit();
						$exhibit->setBuilding($mapItem);
					}
					$exhibit->setName($bldgExhibit['name']);
					$exhibit->setDescription($bldgExhibit['description']);
					$exhibit->setType($exhibitType);
					$exhibit->setAdmissionsTour(0);

					$exhibitErrors = $this->service->validate($exhibit);
					if(count($exhibitErrors) > 0){
						$serialized = $this->serializer->serialize($exhibitErrors, 'json');
						return new Response($serialized, 422, array('Content-Type' => 'application/json'));
					}
					$em->persist($exhibit); // persist but don't save until the end
				}
				// Compare and delete any exhibits not in the updated list
				$this->service->mapItemCollectionCompare($mapItem->getExhibits(), $request->get('exhibits'));

				// Building Services
				foreach($request->get('services') as $bldgService){
					// need to find the service type entity
					$serviceType = $this->doctrine->getRepository(MapServiceType::class)->find($bldgService['type']['id']);
					if(!$serviceType){
						return new Response("The service type was not found.", 404, array('Content-Type' => 'application/json'));
					}
					// a new exhibit won't have an ID
					if(isset($bldgService['id'])){
						$service = $this->doctrine->getRepository(MapItem::class)->find($bldgService['id']);
					}
					else{
						$service = new MapService();
						$service->setBuilding($mapItem);
					}
					$service->setName($bldgService['name']);
					$service->setDescription($bldgService['description']);
					$service->setType($serviceType);
					$service->setAdmissionsTour(0);

					$serviceErrors = $this->service->validate($service);
					if(count($serviceErrors) > 0){
						$serialized = $this->serializer->serialize($serviceErrors, 'json');
						return new Response($serialized, 422, array('Content-Type' => 'application/json'));
					}
					$em->persist($service); // persist but don't save until the end
				}
				// Compare and delete any services not in the updated list
				$this->service->mapItemCollectionCompare($mapItem->getServices(), $request->get('services'));

				// Cycle Dispensers
				foreach($request->get('dispensers') as $bldgDispenser){
					// a new dispenser won't have an ID
					if(isset($bldgDispenser['id'])){
						$dispenser = $this->doctrine->getRepository(MapItem::class)->find($bldgDispenser['id']);
					}
					else{
						$dispenser = new MapDispenser();
						$dispenser->setBuilding($mapItem);
					}
					$dispenser->setName($bldgDispenser['name']);
					$dispenser->setDescription($bldgDispenser['description']);
					$dispenser->setAdmissionsTour(0);

					$dispenserErrors = $this->service->validate($dispenser);
					if(count($dispenserErrors) > 0){
						$serialized = $this->serializer->serialize($dispenserErrors, 'json');
						return new Response($serialized, 422, array('Content-Type' => 'application/json'));
					}
					$em->persist($dispenser); // persist but don't save until the end
				}
				// Compare and delete any dispensers not in the updated list
				$this->service->mapItemCollectionCompare($mapItem->getDispensers(), $request->get('dispensers'));
				break;
			case "bus":
				break;
			case "emergency device":
			case "exhibit":
				// Find the building in which this device/exhibit is located (if any)
				$building = $request->get('building');
				if($building){
					$building = $this->doctrine->getRepository(MapItem::class)->find($building['id']);
				}
				$mapItem->setBuilding($building);
				// Get which type of device/exhibit this is
				$type = $request->get('type');
				if(!$type){
					return new Response("Each ".$itemType." must have a type set.", 400, array('Content-Type' => 'application/json'));
				}
				if($itemType == 'emergency device'){
					$type = $this->doctrine->getRepository(MapEmergencyType::class)->find($type['id']);
				}
				else{
					$type = $this->doctrine->getRepository(MapExhibitType::class)->find($type['id']);
				}
				$mapItem->setType($type);
				break;
			case "parking":
				$mapItem->setSpaces($request->request->get('spaces'));
				$mapItem->setHours($request->request->get('hours'));
				$mapItem->setHasHandicapSpaces($request->request->get('hasHandicapSpaces'));
				// Compare and delete any parking lot types not in the updated list
				$this->service->mapParkingLotTypeCompare($mapItem->getParkingTypes(), $request->get('parkingTypes'), $mapItem);
				break;
			case "service":
				// Find the building in which this device/exhibit is located (if any)
				$building = $request->request->get('building');
				if($building){
					$building = $this->doctrine->getRepository(MapItem::class)->find($building['id']);
				}
				$mapItem->setBuilding($building);

				// Get which type of device/exhibit this is
				$type = $request->get('type');
				if(!$type){
					return new Response("Each ".$itemType." must have a type set.", 400, array('Content-Type' => 'application/json'));
				}
				$mapItem->setType($type);
				break;
			case "dispenser":
				// Find the building in which this dispenser is located
				$building = $request->request->get('building');
				if($building){
					$building = $this->doctrine->getRepository(MapItem::class)->find($building['id']);
				}
				$mapItem->setBuilding($building);
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
		$mapItem->setAdmissionsTour($request->request->get('admissionsTour'));
		if($request->request->get('alias') != ''){
			$mapItem->setAlias($request->request->get('alias'));
		}
		else{
			$mapItem->setAlias(null);
		}

		// validate map item
		$errors = $this->service->validate($mapItem);
		if(count($errors) > 0){
			$serialized = $this->serializer->serialize($errors, 'json');
			return new Response($serialized, 422, array('Content-Type' => 'application/json'));
		}

		// save the map item
		$em->persist($mapItem);
		$em->flush();

		$serialized = $this->serializer->serialize($mapItem, 'json', ['groups' => 'bldgs']);
		return new Response($serialized, 201, array('Content-Type' => 'application/json'));
	}

	/**
	 * Delete a map item from the database
	 * @param $id
	 * @return Response
	 */
	#[Rest\Delete(path: "mapitems/{id}")]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_DELETE")'))]
	public function deleteMapitemAction($id): Response{
		$mapItem = $this->doctrine->getRepository(MapItem::class)->find($id);

		$em = $this->doctrine->getManager();
		$em->remove($mapItem);
		$em->flush();

		return new Response('Item has been deleted.', 204, array('Content-Type' => 'application/json'));
	}
}
