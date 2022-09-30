<?php

namespace App\Service;

use App\Entity\MultimediaRequest\MultimediaRequestStatusNote;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\PersistentCollection;
use App\Entity\MultimediaRequest\MultimediaRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MultimediaRequestService
{
    private $authorizationChecker;
    private $doctrine;
    private $validator;
    private $em;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->validator = $validator;
        $this->em = $em;
    }

    /**
     * Use the Symfony container's validator to validate fields for an assignee
     * @param mixed $item
     * @return ConstraintViolationList $errors
     */
    public function validate(mixed $item): ConstraintViolationList
    {
        return $this->validator->validate($item);
    }

    /**
     * Compare and delete any status notes not in the updated array. Add any status notes not in the existing array.
     *
     * @param PersistentCollection $currentCollection
     * @param array $updatedArray
     * @return void
     */
    public function statusNoteCollectionCompare(PersistentCollection $currentCollection, array $updatedArray): void
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
            $item = $this->doctrine->getRepository(MultimediaRequestStatusNote::class)->find($itemToDelete);
            $this->deleteStatusNote($item);
        }
    }

    /**
     *  Fetch the user's permissions for managing photo requests
     * @return array $multimediaRequestPermissions
     */
    #[ArrayShape(['create' => "bool", 'edit' => "bool", 'delete' => "bool", 'admin' => "bool", 'email' => "bool"])]
    public function getUserMultimediaRequestPermissions(): array
    {
        $multimediaRequestPermissions = array(
            'create' => false,
            'edit' => false,
            'delete' => false,
            'admin' => false
        );
        if ($this->authorizationChecker->isGranted('ROLE_MULTIMEDIA_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
            $multimediaRequestPermissions['create'] = true;
            $multimediaRequestPermissions['edit'] = true;
            $multimediaRequestPermissions['delete'] = true;
            $multimediaRequestPermissions['email'] = true;
            $multimediaRequestPermissions['admin'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MULTIMEDIA_EMAIL')) {
            $multimediaRequestPermissions['email'] = true;
            $multimediaRequestPermissions['edit'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MULTIMEDIA_DELETE')) {
            $multimediaRequestPermissions['delete'] = true;
            $multimediaRequestPermissions['edit'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MULTIMEDIA_EDIT')) {
            $multimediaRequestPermissions['create'] = true;
            $multimediaRequestPermissions['edit'] = true;
        }
        if ($this->authorizationChecker->isGranted('ROLE_MULTIMEDIA_CREATE')) {
            $multimediaRequestPermissions['create'] = true;
        }
        return $multimediaRequestPermissions;
    }


    /**
     * Remove a multimedia request from the database
     * @param MultimediaRequest $multimediaRequest
     * @return void
     */
    protected function deleteMultimediaRequest(MultimediaRequest $multimediaRequest): void
    {
        $this->em->remove($multimediaRequest);
        $this->em->flush();
    }

    /**
     * Remove a status note from the database
     * @param MultimediaRequestStatusNote $note
     * @return void
     */
    protected function deleteStatusNote(MultimediaRequestStatusNote $note): void
    {
        $this->em->remove($note);
        $this->em->flush();
    }
}
