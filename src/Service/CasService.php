<?php
namespace App\Service;

use App\Entity\GradCas\GradCasCycle;
use App\Entity\GradCas\GradCasLink;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GradCasService {
	private AuthorizationCheckerInterface $authorizationChecker;
	private ValidatorInterface $validator;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;

	public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em) {
		$this->authorizationChecker = $authorizationChecker;
		$this->validator = $validator;
		$this->doctrine = $doctrine;
		$this->em = $em;
	}

	#[ArrayShape(['view' => "bool", 'edit' => "bool", 'admin' => "bool"])]
	public function getUserGradCasPermissions(): array
	{
		$permissions = array(
			'view' => false,
			'edit' => false,
			'admin' => false
		);

		if ($this->authorizationChecker->isGranted('ROLE_GRADCAS_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
			$permissions['view'] = true;
			$permissions['edit'] = true;
			$permissions['admin'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_GRADCAS_EDIT')) {
			$permissions['view'] = true;
			$permissions['edit'] = true;
		}

		if ($this->authorizationChecker->isGranted('ROLE_GRADCAS_VIEW')) {
			$permissions['view'] = true;
		}

		return $permissions;
	}

	public function validate($entity): ConstraintViolationList {
		return $this->validator->validate($entity);
	}

	public function getLinksPagination(int $cycleId, int $currentPage, int $pageSize): array
	{
		$repository = $this->doctrine->getRepository(GradCasLink::class);
		return $repository->paginatedLinks($cycleId, $currentPage, $pageSize);
	}

	public function getLinksByName(string $searchTerm, int $cycleId): array
	{
		$repository = $this->doctrine->getRepository(GradCasLink::class);
		return $repository->searchLinks($searchTerm, $cycleId);
	}

	/**
	 * Fetches graduate programs from the programs database for the degree picker.
	 */
	public function getGraduatePrograms(): array
	{
		$programsEm = $this->doctrine->getManager('programs');
		$conn = $programsEm->getConnection();

		$sql = "
			SELECT p.id, p.program, p.full_name, pd.degree
			FROM programs.program_programs p
			LEFT JOIN programs.program_degrees pd ON p.degree_id = pd.id
			WHERE p.catalog = 'graduate'
			ORDER BY p.full_name ASC
		";

		return $conn->executeQuery($sql)->fetchAllAssociative();
	}
}
