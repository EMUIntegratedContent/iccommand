<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\PersistentCollection;
use App\Entity\Map\MapItem;
use App\Entity\Map\MapBuilding;
use App\Entity\Map\MapBuildingType;
use App\Entity\Map\MapParking;
use App\Entity\Map\MapParkingType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MapItemService
{
    private $authorizationChecker;
    private $doctrine;
    private $validator;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->validator = $validator;
    }

    /**
     * Use the Symfony container's validator to validate fields for a map item
     * @param MapItem $mapItem
     * @return ConstraintViolationList $errors
     */
    public function validate(MapItem $mapItem): ConstraintViolationList
    {
        return $this->validator->validate($mapItem);
    }

    /**
     * Compare and delete any items that do not appear in the updated array.
     *
     * @param PersistentCollection $currentCollection
     * @param array $updatedArray
     */
    public function mapItemCollectionCompare(PersistentCollection $currentCollection, array $updatedArray): void
    {
        $current_ids = array();
        foreach ($currentCollection as $item) {
            $current_ids[] = $item->getId();
        }
        $updated_ids = array();
        foreach ($updatedArray as $item) {
            // new bathrooms will NOT have an id
            if (isset($item['id'])) {
                $updated_ids[] = $item['id'];
            }
        }
        $itemsToDelete = array_diff($current_ids, $updated_ids); // result is the items that do NOT appear in the updated IDs

        // Find and remove any items that do NOT appear in the updated IDs
        foreach ($itemsToDelete as $itemToDelete) {
            $item = $this->doctrine->getRepository(MapItem::class)->find($itemToDelete);
            $this->deleteMapItem($item);
        }
    }

    /**
     * Compare and delete any building types not in the updated array. Add any building types not in the existing array.
     *
     * @param PersistentCollection $currentCollection
     * @param array $updatedArray
     * @param MapBuilding $building
     */
    public function mapBuildingTypeCompare(PersistentCollection $currentCollection, array $updatedArray, MapBuilding $building): void
    {
        $current_ids = array();
        foreach ($currentCollection as $item) {
            $current_ids[] = $item->getId();
        }
        $updated_ids = array();
        foreach ($updatedArray as $item) {
            // new bathrooms will NOT have an id
            if (isset($item['id'])) {
                $updated_ids[] = $item['id'];
            }
        }
        $itemsToRemove = array_diff($current_ids, $updated_ids); // result is the items that do NOT appear in the updated IDs

        // add all IDs appearing in the updated array (if they already exist, the setter method in the building class will be sure to not add them again)
        foreach ($updated_ids as $itemToAdd) {
            $item = $this->doctrine->getRepository(MapBuildingType::class)->find($itemToAdd);
            $item->addBuilding($building);
        }
        // Find and remove any items that do NOT appear in the updated IDs
        foreach ($itemsToRemove as $itemToRemove) {
            $item = $this->doctrine->getRepository(MapBuildingType::class)->find($itemToRemove);
            $item->removeBuilding($building);
        }
    }

    /**
     * Compare and delete any parking types not in the updated array. Add any parking types not in the existing array.
     *
     * @param PersistentCollection $currentCollection
     * @param array $updatedArray
     * @param MapParking $parkingLot
     */
    public function mapParkingLotTypeCompare(PersistentCollection $currentCollection, array $updatedArray, MapParking $parkingLot): void
    {
        $current_ids = array();
        foreach ($currentCollection as $item) {
            $current_ids[] = $item->getId();
        }
        $updated_ids = array();
        foreach ($updatedArray as $item) {
            // new bathrooms will NOT have an id
            if (isset($item['id'])) {
                $updated_ids[] = $item['id'];
            }
        }
        $itemsToRemove = array_diff($current_ids, $updated_ids); // result is the items that do NOT appear in the updated IDs

        // add all IDs appearing in the updated array (if they already exist, the setter method in the parking class will be sure to not add them again)
        foreach ($updated_ids as $itemToAdd) {
            $item = $this->doctrine->getRepository(MapParkingType::class)->find($itemToAdd);
            $item->addParkingLot($parkingLot);
        }
        // Find and remove any items that do NOT appear in the updated IDs
        foreach ($itemsToRemove as $itemToRemove) {
            $item = $this->doctrine->getRepository(MapParkingType::class)->find($itemToRemove);
            $item->removeParkingLot($parkingLot);
        }
    }

    /**
     * Fetch the user's permissions for managing map items
     * @return array $mapPermissions
     */
    public function getUserMapPermissions(): array
    {
        $mapPermissions = array(
            'create' => false,
            'edit' => false,
            'delete' => false,
            'imageUpload' => false,
            'admin' => false
        );
        if ($this->authorizationChecker->isGranted('ROLE_MAP_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
            $mapPermissions['create'] = true;
            $mapPermissions['edit'] = true;
            $mapPermissions['delete'] = true;
            $mapPermissions['imageUpload'] = true;
            $mapPermissions['admin'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MAP_IMAGE_UPLOAD')) {
            $mapPermissions['edit'] = true;
            $mapPermissions['imageUpload'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MAP_DELETE')) {
            $mapPermissions['delete'] = true;
            $mapPermissions['edit'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MAP_EDIT')) {
            $mapPermissions['create'] = true;
            $mapPermissions['edit'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MAP_CREATE')) {
            $mapPermissions['create'] = true;
        }
        return $mapPermissions;
    }

    /**
     * Remove a map item from the database
     * @param MapItem $mapItem
     */
    protected function deleteMapItem(MapItem $mapItem): void
    {
        $em = $this->doctrine->getManager();
        $em->remove($mapItem);
        $em->flush();
    }
}
