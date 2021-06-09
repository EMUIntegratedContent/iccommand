<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\PersistentCollection;
use App\Entity\OuCampusSignups;

class OusignupListService
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Use the Symfony container's validator to validate fields for an assignee
     * @param App\Entity\OuCampusSignups\* $item
     * @return array $errors
     */
    public function validate($item): ConstraintViolationList
    {
        $validator = $this->container->get('validator');
        $errors = $validator->validate($item);
        return $errors;
    }
 
}
