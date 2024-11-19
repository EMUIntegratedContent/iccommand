<?php
namespace App\Controller\Programs;

use App\Entity\Programs\Programs;
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

  /**
   * The create page of the program.
   */
	#[Route('/programs/create', name: 'programs_create')]
  public function add(): Response
  {
    $permissions = json_encode($this->service->getProgramsPermissions());
    return $this->render('programs/create.html.twig', ['permissions' => $permissions]);
  }

  /**
   * The edit page of the programs.
   */
	#[Route('/programs/{id}/edit', name: 'programs_edit')]
  public function edit($id): Response
  {
    $program = $this->doctrine->getRepository(Programs::class)->find($id);

    if (!$program) {
      throw $this->createNotFoundException('This program does not exist.');
    }

    $permissions = json_encode($this->service->getProgramsPermissions());

    return $this->render('programs/edit.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }

  /**
   * The management page of the programs.
   */
	#[Route('/programs/manage', name: 'programs_manage')]
  public function manage(): Response
  {
    return $this->render('programs/manage.html.twig', []);
  }

  /**
   * The show page of the program.
   */
	#[Route('/programs/{id}', name: 'programs_show')]
  public function show($id): Response
  {
    $program = $this->doctrine->getRepository(Programs::class)->find($id);

    if (!$program) {
      throw $this->createNotFoundException('This program does not exist.');
    }

    $permissions = json_encode($this->service->getProgramsPermissions());

    return $this->render('programs/show.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }
}
