<?php
namespace App\Controller\PhotoRequest;

use App\Service\PhotoRequestService;
use App\Service\ProgramsService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The internal controller for photo requests.
 */
class PhotoRequestController extends AbstractController {
  private PhotoRequestService $service;
  private ManagerRegistry $doctrine;

  /**
   * The constructor of the controller for the programs.
   * @param PhotoRequestService $service The service of the programs.
   */
  public function __construct(PhotoRequestService $service, ManagerRegistry $doctrine)
  {
    $this->service = $service;
    $this->doctrine = $doctrine;
  }

  /**
   * The index page of the catalog programs.
   */
	#[Route('/photo', name: 'photo_index')]
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
	 * The management page of the programs.
	 */
	#[Route('/programs/manage', name: 'programs_manage')]
	public function manage(): Response
	{
		return $this->render('programs/manage.html.twig', []);
	}

	/**
	 * The programs websites list (needs to be managed separately because of lack of FK constraints year-to-year).
	 */
	#[Route('/programs/websites', name: 'websites')]
	public function websites(): Response
	{
		$permissions = json_encode($this->service->getProgramsPermissions());
		return $this->render('programs/websites.html.twig', ['permissions' => $permissions]);
	}

	/**
	 * The view page of the website.
	 */
	#[Route('/programs/websites/{id}', name: 'website_show')]
	public function websitesShow($id): Response
	{
		$permissions = json_encode($this->service->getProgramsPermissions());

		return $this->render('programs/website_show.html.twig', [
			'id' => $id,
			'permissions' => $permissions
		]);
	}

	/**
	 * The edit page of the website.
	 */
	#[Route('/programs/websites/{id}/edit', name: 'website_edit')]
	public function websitesEdit($id): Response
	{
		$permissions = json_encode($this->service->getProgramsPermissions());

		return $this->render('programs/website_edit.html.twig', [
			'id' => $id,
			'permissions' => $permissions
		]);
	}



  /**
   * The edit page of the programs.
   */
	#[Route('/programs/{id}/edit', name: 'programs_edit')]
  public function edit($id): Response
  {
    $permissions = json_encode($this->service->getProgramsPermissions());

    return $this->render('programs/edit.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }

  /**
   * The show page of the program.
   */
	#[Route('/programs/{id}', name: 'programs_show')]
  public function show($id): Response
  {
    $permissions = json_encode($this->service->getProgramsPermissions());

    return $this->render('programs/show.html.twig', [
      'id' => $id,
      'permissions' => $permissions
    ]);
  }
}
