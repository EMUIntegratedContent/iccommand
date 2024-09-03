<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{

	#[Route('/', name: 'home')]
  public function index(): Response
  {
    $currentUser = null;
    $currentUserRoles = null;

    if($this->getUser()){
      $user = $this->getUser();
      $currentUser = $user->getUsername();
      $currentUserRoles = json_encode($this->getUser()->getRoles());
    }

    return $this->render('index.html.twig', [
      'currentUsername' => $currentUser,
      'currentUserRoles' => $currentUserRoles,
    ]);
  }

	#[Route('/profile', name: 'user_profile')]
  public function profile(): Response
  {
    return $this->render('profile.html.twig', []);
  }

	#[Route('/unittest', name: 'unit_test')]
  public function unitTest(): Response
  {
    return new Response('Hello World', 201, array('Content-Type' => 'application/json; charset=UTF-8'));
  }
}
