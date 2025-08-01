<?php

namespace App\Controller\Directory;

use App\Entity\Directory\Department;
use App\Service\DirectoryService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the departments directory.
 */
class DirectoryController extends AbstractController
{
  private DirectoryService $service;
  private ManagerRegistry $doctrine;

  /**
   * The constructor of the controller for the directory.
   * @param DirectoryService $service The service of the directory.
   */
  public function __construct(DirectoryService $service, ManagerRegistry $doctrine)
  {
    $this->service = $service;
    $this->doctrine = $doctrine;
  }

  /**
   * The index page of the directory.
   */
  #[Route('/directory', name: 'directory_index')]
  public function index(): Response
  {
    $permissions = json_encode($this->service->getUserDepartmentPermissions());
    return $this->render('directory/index.html.twig', [
      'permissions' => $permissions,
      'controller_name' => 'Directory'
    ]);
  }

  /**
   * The create page of the directory.
   */
  #[Route('/directory/create', name: 'directory_create')]
  public function add(): Response
  {
    $permissions = json_encode($this->service->getUserDepartmentPermissions());
    return $this->render('directory/create.html.twig', ['permissions' => $permissions]);
  }

  /**
   * The edit page of the directory.
   */
  #[Route('/directory/{id}/edit', name: 'directory_edit')]
  public function edit($id): Response
  {
    $department = $this->doctrine->getRepository(Department::class)->find($id);

    if (!$department) {
      throw $this->createNotFoundException('This department does not exist.');
    }

    $permissions = json_encode($this->service->getUserDepartmentPermissions());

    return $this->render('directory/edit.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }

  /**
   * The management page of the department directory app.
   */
  #[Route('/directory/manage', name: 'directory_manage')]
  public function manage(): Response
  {
    return $this->render('directory/manage.html.twig', []);
  }

  /**
   * The show page of the directory.
   */
  #[Route('/directory/{id}', name: 'directory_show')]
  public function show($id): Response
  {
    $department = $this->doctrine->getRepository(Department::class)->find($id);

    if (!$department) {
      throw $this->createNotFoundException('This department does not exist.');
    }

    $permissions = json_encode($this->service->getUserDepartmentPermissions());

    return $this->render('directory/show.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }
}
