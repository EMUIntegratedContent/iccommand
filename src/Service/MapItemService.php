<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\ORM\PersistentCollection;
use App\Entity\Map\MapItem;
use App\Entity\Map\MapBathroom;
use App\Entity\Map\MapBuilding;
use App\Entity\Map\MapBuildingType;
use App\Entity\Map\MapEmergency;
use App\Entity\Map\MapEmergencyType;
use App\Entity\Map\MapExhibit;
use App\Entity\Map\MapExhibitType;
use App\Entity\Map\MapParking;
use App\Entity\Map\MapParkingType;

class MapItemService
{
  private $container;

  public function __construct(Container $container)
  {
    $this->container = $container;
  }

  // NEEDS TO GO INTO A SERVICE
  public function validate($mapItem){
    $validator = $this->container->get('validator');
    $errors = $validator->validate($mapItem);

    return $errors;
  }

  public function mapItemCollectionCompare(PersistentCollection $currentCollection, array $updatedArray)
  {
    $current_ids = array();
    foreach($currentCollection as $item){
      $current_ids[] = $item->getId();
    }
    $updated_ids = array();
    foreach($updatedArray as $item){
      // new bathrooms will NOT have an id
      if(isset($item['id'])){
        $updated_ids[] = $item['id'];
      }
    }
    $itemsToDelete = array_diff($current_ids, $updated_ids); // result is the items that do NOT appear in the updated IDs

    // Find and remove any items that do NOT appear in the updated IDs
    foreach($itemsToDelete as $itemToDelete){
      $item = $this->container->get('doctrine')->getRepository(MapItem::class)->find($itemToDelete);
      $this->deleteMapItem($item);
    }
  }

  public function mapBuildingTypeCompare(PersistentCollection $currentCollection, array $updatedArray, MapBuilding $building)
  {
    $current_ids = array();
    foreach($currentCollection as $item){
      $current_ids[] = $item->getId();
    }
    $updated_ids = array();
    foreach($updatedArray as $item){
      // new bathrooms will NOT have an id
      if(isset($item['id'])){
        $updated_ids[] = $item['id'];
      }
    }
    $itemsToRemove = array_diff($current_ids, $updated_ids); // result is the items that do NOT appear in the updated IDs

    // add all IDs appearing in the updated array (if they already exist, the setter method in the building class will be sure to not add them again)
    foreach($updated_ids as $itemToAdd){
      $item = $this->container->get('doctrine')->getRepository(MapBuildingType::class)->find($itemToAdd);
      $item->addBuilding($building);
    }
    // Find and remove any items that do NOT appear in the updated IDs
    foreach($itemsToRemove as $itemToRemove){
      $item = $this->container->get('doctrine')->getRepository(MapBuildingType::class)->find($itemToRemove);
      $item->removeBuilding($building);
    }
  }

  public function mapParkingLotTypeCompare(PersistentCollection $currentCollection, array $updatedArray, MapParking $parkingLot)
  {
    $current_ids = array();
    foreach($currentCollection as $item){
      $current_ids[] = $item->getId();
    }
    $updated_ids = array();
    foreach($updatedArray as $item){
      // new bathrooms will NOT have an id
      if(isset($item['id'])){
        $updated_ids[] = $item['id'];
      }
    }
    $itemsToRemove = array_diff($current_ids, $updated_ids); // result is the items that do NOT appear in the updated IDs

    // add all IDs appearing in the updated array (if they already exist, the setter method in the parking class will be sure to not add them again)
    foreach($updated_ids as $itemToAdd){
      $item = $this->container->get('doctrine')->getRepository(MapParkingType::class)->find($itemToAdd);
      $item->addParkingLot($parkingLot);
    }
    // Find and remove any items that do NOT appear in the updated IDs
    foreach($itemsToRemove as $itemToRemove){
      $item = $this->container->get('doctrine')->getRepository(MapParkingType::class)->find($itemToRemove);
      $item->removeParkingLot($parkingLot);
    }
  }

  protected function deleteMapItem(MapItem $mapItem)
  {
    $em = $this->container->get('doctrine')->getManager();
    $em->remove($mapItem);
    $em->flush();
  }
}