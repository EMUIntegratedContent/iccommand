<?php

namespace App\Controller\Api\CrimeLog;

//use Doctrine\Persistence\CrimeLog;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class CrimeLogController extends AbstractFOSRestController
{

	#[Route('/crimelog', name: 'crimelog_index')]
	public function index(): Response
	{
		return $this->render('crimelog/index.html.twig');
	}
}
