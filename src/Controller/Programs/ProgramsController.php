<?php
namespace App\Controller\Programs;

use App\Service\ProgramsService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the catalog programs.
 */
class ProgramsController extends AbstractController {
  private ProgramsService $service;
  private ManagerRegistry $doctrine;

  /**
   * The constructor of the controller for the programs.
   * @param ProgramsService $service The service of the programs.
   */
  public function __construct(ProgramsService $service, ManagerRegistry $doctrine)
  {
    $this->service = $service;
    $this->doctrine = $doctrine;
  }

  /**
   * The index page of the catalog programs.
   */
	#[Route('/programs', name: 'programs_index')]
  public function index(): Response
  {
    $permissions = json_encode($this->service->getProgramsPermissions());
    return $this->render('programs/index.html.twig', [
      'permissions' => $permissions,
      'controller_name' => 'Master'
    ]);
  }

  /**
   * The list page of the programs.
   */
	#[Route('/programs/list', name: 'programs_list')]
  public function list(): Response
  {
    $permissions = json_encode($this->service->getProgramsPermissions());
    return $this->render('programs/list.html.twig', ['permissions' => $permissions]);
  }

//  /**
//   * The create page of the redirects.
//   */
//	#[Route('/programs/create', name: 'programs_create')]
//  public function add(): Response
//  {
//    $permissions = json_encode($this->service->getUserRedirectPermissions());
//    return $this->render('redirect/create.html.twig', ['permissions' => $permissions]);
//  }
//
//  /**
//   * The edit page of the programs.
//   */
//	#[Route('/programs/{id}/edit', name: 'programs_edit')]
//  public function edit($id): Response
//  {
//    $redirect = $this->doctrine->getRepository(Redirect::class)->find($id);
//
//    if (!$redirect) {
//      throw $this->createNotFoundException('This request does not exist.');
//    }
//
//    $itemType = $redirect->getItemType();
//    $permissions = json_encode($this->service->getUserRedirectPermissions());
//
//    return $this->render('redirect/edit.html.twig', [
//      'id' => $id,
//      'itemType' => $itemType,
//      'permissions' => $permissions
//    ]);
//  }
//
//  /**
//   * The management page of the programs.
//   */
//	#[Route('/programs/manage', name: 'redirects_manage')]
//  public function manage(): Response
//  {
//    return $this->render('redirect/manage.html.twig', []);
//  }
//
//  /**
//   * The show page of the programs.
//   */
//	#[Route('/programs/{id}', name: 'redirects_show')]
//  public function show($id): Response
//  {
//    $redirect = $this->doctrine->getRepository(Redirect::class)->find($id);
//
//    if (!$redirect) {
//      throw $this->createNotFoundException('This redirect does not exist.');
//    }
//
//    $itemType = $redirect->getItemType();
//    $permissions = json_encode($this->service->getUserRedirectPermissions());
//
//    return $this->render('redirect/show.html.twig', [
//      'id' => $id,
//      'itemType' => $itemType,
//      'permissions' => $permissions
//    ]);
//  }
}
