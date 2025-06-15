<?php

namespace App\Controller\CrimeLog;
//use App\Entity\Programs\Programs;
//use Doctrine\Persistence\CrimeLog;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * The index page of the crimelog.
 */
class CrimeLogController extends AbstractController
{
	#[Route('/crimelog', name: 'crimelog_index')]
	public function index(): Response
	{
		return $this->render('crimelog/index.html.twig');
	}
}
