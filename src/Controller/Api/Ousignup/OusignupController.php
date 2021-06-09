<?php

namespace App\Controller\Ousignup;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\OusignupListService;


class OusignupController extends AbstractController
{
    private $service;

    public function __construct(OusignupListService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/ousignup", name="ousignup_ousignup")
     */
    public function index(OusignupListService $service): Response
    {
 
        return $this->render('ousignup/index.html.twig', [
            'controller_name' => 'OusignupController',
        ]);
    }

}
