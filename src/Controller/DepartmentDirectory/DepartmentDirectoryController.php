<?php

namespace App\Controller\DepartmentDirectory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\OusignupListService;

class DepartmentDirectoryController extends AbstractController
{
    private $service;

    public function __construct(OusignupListService $service)
    {
        $this->service = $service;
    }
    /**
     * @Route("/departmentdirectory", name="department_directory_department_directory")
     */
    public function index(): Response
    {
        return $this->render('department_directory/index.html.twig', [
            'controller_name' => 'DepartmentDirectoryController',
        ]);
    }
}
