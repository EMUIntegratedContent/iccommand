<?php

namespace App\Service;

use App\Entity\MultimediaRequest\MultimediaRequestStatusNote;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\PersistentCollection;
use App\Entity\MultimediaRequest\MultimediaRequest;

class MultimediaRequestService
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Use the Symfony container's validator to validate fields for an assignee
     * @param App\Entity\MultimediaRequest\* $item
     * @return array $errors
     */
    public function validate($item): ConstraintViolationList
    {
        $validator = $this->container->get('validator');
        $errors = $validator->validate($item);
        return $errors;
    }

    /**
     * Compare and delete any status notes not in the updated array. Add any status notes not in the existing array.
     *
     * @param PersistentCollection $currentCollection
     * @param array $updatedArray
     */
    public function statusNoteCollectionCompare(PersistentCollection $currentCollection, array $updatedArray)
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
            $item = $this->container->get('doctrine')->getRepository(MultimediaRequestStatusNote::class)->find($itemToDelete);
            $this->deleteStatusNote($item);
        }
    }

    /**
     *  Fetch the user's permissions for managing photo requests
     * @return array $multimediaRequestPermissions
     */
    public function getUserMultimediaRequestPermissions()
    {
        $multimediaRequestPermissions = array(
            'create' => false,
            'edit' => false,
            'delete' => false,
            'admin' => false
        );
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('ROLE_GLOBAL_ADMIN')) {
            $multimediaRequestPermissions['create'] = true;
            $multimediaRequestPermissions['edit'] = true;
            $multimediaRequestPermissions['delete'] = true;
            $multimediaRequestPermissions['email'] = true;
            $multimediaRequestPermissions['admin'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_EMAIL')) {
            $multimediaRequestPermissions['email'] = true;
            $multimediaRequestPermissions['edit'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_DELETE')) {
            $multimediaRequestPermissions['delete'] = true;
            $multimediaRequestPermissions['edit'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_EDIT')) {
            $multimediaRequestPermissions['create'] = true;
            $multimediaRequestPermissions['edit'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_MULTIMEDIA_CREATE')) {
            $multimediaRequestPermissions['create'] = true;
        }
        return $multimediaRequestPermissions;
    }


    /**
     * Remove a multimedia request from the database
     * @param MultimediaRequest $multimediaRequest
     */
    protected function deleteMultimediaRequest(MultimediaRequest $multimediaRequest)
    {
        $em = $this->container->get('doctrine')->getManager();
        $em->remove($multimediaRequest);
        $em->flush();
    }

    /**
     * Remove a status note from the database
     * @param MultimediaRequestStatusNote $note
     */
    protected function deleteStatusNote(MultimediaRequestStatusNote $note)
    {
        $em = $this->container->get('doctrine')->getManager();
        $em->remove($note);
        $em->flush();
    }
}
