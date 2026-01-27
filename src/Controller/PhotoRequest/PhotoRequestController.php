<?php

namespace App\Controller\PhotoRequest;

use App\Service\PhotoRequestService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for photo requests.
 */
class PhotoRequestController extends AbstractController
{
  private PhotoRequestService $service;
  private ManagerRegistry $doctrine;

  /**
   * The constructor of the controller for photo requests.
   * @param PhotoRequestService $service The service of the photo requests.
   */
  public function __construct(PhotoRequestService $service, ManagerRegistry $doctrine)
  {
    $this->service = $service;
    $this->doctrine = $doctrine;
  }

  /**
   * The index page of the photo requests.
   */
  #[Route('/photorequests', name: 'photorequests_index')]
  public function index(): Response
  {
    $permissions = json_encode($this->service->getPhotoRequestPermissions());
    return $this->render('photorequests/index.html.twig', [
      'permissions' => $permissions,
      'controller_name' => 'PhotoRequest'
    ]);
  }

  /**
   * The create page of the photo request.
   */
  #[Route('/photorequests/create', name: 'photorequests_create')]
  public function add(): Response
  {
    $permissions = json_encode($this->service->getPhotoRequestPermissions());
    return $this->render('photorequests/create.html.twig', ['permissions' => $permissions]);
  }

  /**
   * The management page of the photo requests.
   */
  #[Route('/photorequests/manage', name: 'photorequests_manage')]
  public function manage(): Response
  {
    $permissions = json_encode($this->service->getPhotoRequestPermissions());
    return $this->render('photorequests/manage.html.twig', ['permissions' => $permissions]);
  }

  /**
   * The edit page of the photo request.
   */
  #[Route('/photorequests/{id}/edit', name: 'photorequests_edit')]
  public function edit($id): Response
  {
    $permissions = json_encode($this->service->getPhotoRequestPermissions());

    return $this->render('photorequests/edit.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }

  /**
   * The show page of the photo request.
   */
  #[Route('/photorequests/{id}', name: 'photorequests_show')]
  public function show($id): Response
  {
    $permissions = json_encode($this->service->getPhotoRequestPermissions());

    return $this->render('photorequests/show.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }
}
