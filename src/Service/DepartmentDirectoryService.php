<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\PersistentCollection;
use App\Entity\DirectoryDepartments;

class DepartmentDirectoryService
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Use the Symfony container's validator to validate fields for an assignee
     * @param App\Entity\DirectoryDepartments\* $item
     * @return array $errors
     */
    public function validate($item): ConstraintViolationList
    {
        $validator = $this->container->get('validator');
        $errors = $validator->validate($item);
        return $errors;
    }

    /**
     *  Fetch the user's permissions for managing photo requests
     * @return array $departmentDirectoryPermissions
     */
    public function getUserDepartmentDirectoryPermissions()
    {
        $departmentDirectoryPermissions = array(
            'create' => false,
            'edit' => false,
            'delete' => false,
            'admin' => false
        );
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_DEPARTMENT_DIRECTORY_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('ROLE_GLOBAL_ADMIN')) {
            $departmentDirectoryPermissions['create'] = true;
            $departmentDirectoryPermissions['edit'] = true;
            $departmentDirectoryPermissions['delete'] = true;
            $departmentDirectoryPermissions['email'] = true;
            $departmentDirectoryPermissions['admin'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_DEPARTMENT_DIRECTORY_EMAIL')) {
            $departmentDirectoryPermissions['email'] = true;
            $departmentDirectoryPermissions['edit'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_DEPARTMENT_DIRECTORY_DELETE')) {
            $departmentDirectoryPermissions['delete'] = true;
            $departmentDirectoryPermissions['edit'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_DEPARTMENT_DIRECTORY_EDIT')) {
            $departmentDirectoryPermissions['create'] = true;
            $departmentDirectoryPermissions['edit'] = true;
        }
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_DEPARTMENT_DIRECTORY_CREATE')) {
            $departmentDirectoryPermissions['create'] = true;
        }
        return $departmentDirectoryPermissions;
    }
 
}
