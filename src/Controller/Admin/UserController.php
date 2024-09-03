<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/admin/users', name: 'users')]
class UserController extends AbstractController
{
	private UserService $service;
	private SerializerInterface $serializer;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;

	public function __construct(UserService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em){
		$this->service = $service;
		$this->serializer = $serializer;
		$this->doctrine = $doctrine;
		$this->em = $em;
	}

	#[Route('/', name: 'users_index')]
	public function index(): Response
	{
		$users = $this->doctrine->getRepository(User::class)->findAll();
		$roles = $this->getParameter('security.role_hierarchy.roles');
		return $this->render('admin/users/index.html.twig', [
			'roles' => $roles,
			'users' => $users,
		]);
	}

	#[Route('/{username}', name: 'user_show')]
	public function show($username): Response
	{
		$user = $this->doctrine->getRepository(User::class)->findOneByUsername($username);
		if(!$user){
			throw $this->createNotFoundException('The user name ' . $username . ' was not found.');
		}
		return $this->render('admin/users/show.html.twig', [
			'user' => $user,
		]);
	}
}
