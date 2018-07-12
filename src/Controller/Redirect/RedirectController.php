<?php
namespace App\Controller\Redirect;

use App\Entity\Redirect\Redirect;
use App\Service\RedirectService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * The controller for the redirects.
 */
class RedirectController extends Controller {
  private $service;

  /**
   * The constructor of the controller for the redirects.
   * @param RedirectService $service The service of the redirects.
   */
  public function __construct(RedirectService $service)
  {
    $this->service = $service;
  }

  /**
   * The index page of the redirects.
   * @Route("/redirects", name="redirect_index")
   */
  public function index() {
    $permissions = json_encode($this->service->getUserRedirectPermissions());
    return $this->render('redirect/index.html.twig', [
      'permissions' => $permissions,
      'controller_name' => 'Master'
    ]);
  }

  /**
   * The create page of the redirects.
   * @Route("/redirects/create", name="redirects_create")
   */
  public function add() {
    $permissions = json_encode($this->service->getUserRedirectPermissions());
    return $this->render('redirect/create.html.twig', ['permissions' => $permissions]);
  }

  /**
   * The edit page of the redirects.
   * @Route("/redirects/{id}/edit", name="redirects_edit")
   */
  public function edit($id) {
    $redirect = $this->getDoctrine()->getRepository(Redirect::class)->find($id);

    if (!$redirect) {
      throw $this->createNotFoundException('This request does not exist.');
    }

    $itemType = $redirect->getItemType();
    $permissions = json_encode($this->service->getUserRedirectPermissions());

    return $this->render('redirect/edit.html.twig', [
      'id' => $id,
      'itemType' => $itemType,
      'permissions' => $permissions
    ]);
  }

  /**
   * The management page of the redirects.
   * @Route("/redirects/manage", name="redirects_manage")
   */
  public function manage() {
    return $this->render('redirect/manage.html.twig', []);
  }

  /**
   * The list page of the redirects.
   * @Route("/redirects/list", name="redirects_list")
   */
  public function list() {
    $permissions = json_encode($this->service->getUserRedirectPermissions());
    return $this->render('redirect/list.html.twig', ['permissions' => $permissions]);
  }

  /**
   * The show page of the redirects.
   * @Route("/redirects/{id}", name="redirects_show")
   */
  public function show($id) {
    $redirect = $this->getDoctrine()->getRepository(Redirect::class)->find($id);

    if (!$redirect) {
      throw $this->createNotFoundException('This redirect does not exist.');
    }

    $itemType = $redirect->getItemType();
    $permissions = json_encode($this->service->getUserRedirectPermissions());

    return $this->render('redirect/show.html.twig', [
      'id' => $id,
      'itemType' => $itemType,
      'permissions' => $permissions
    ]);
  }
}
