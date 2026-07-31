<?php

namespace App\Service;

use App\Entity\SocialMedia\SocialMedia;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The social media service manages social media link entities, resolves the
 * current user's permissions, and validates entities.
 */
class SocialMediaService
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private ValidatorInterface $validator;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $em;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, ManagerRegistry $doctrine, EntityManagerInterface $em)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
        $this->doctrine = $doctrine;
        $this->em = $em;
    }

    /**
     * Removes a social media entity from the database.
     * @param SocialMedia $socialMedia The entity to be removed.
     */
    public function deleteSocialMedia(SocialMedia $socialMedia): void
    {
        $this->em->remove($socialMedia);
        $this->em->flush();
    }

    /**
     * Fetches the permissions of the user for managing social media entities.
     * @return array The user's permissions.
     */
    public function getUserSocialMediaPermissions(): array
    {
        // Set all permissions to false as default.
        $permissions = array(
            'user' => false,
            'admin' => false
        );

        // The admins automatically have all the permissions.
        if ($this->authorizationChecker->isGranted('ROLE_SOCIAL_ADMIN') || $this->authorizationChecker->isGranted('ROLE_GLOBAL_ADMIN')) {
            $permissions['user'] = true;
            $permissions['admin'] = true;
        }

        // The non-admins have the "user" permission.
        if ($this->authorizationChecker->isGranted('ROLE_SOCIAL_USER')) {
            $permissions['user'] = true;
        }

        return $permissions;
    }

    /**
     * Uses the Symfony container's validator to validate fields for an entity.
     * @param SocialMedia $socialMedia A social media entity.
     * @return ConstraintViolationList A list of errors.
     */
    public function validate($socialMedia): ConstraintViolationList
    {
        return $this->validator->validate($socialMedia);
    }

    /**
     * Gets the social media entities with pagination.
     * @param int $currentPage
     * @param int $pageSize
     * @param string $searchTerm
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSocialMediaPagination(int $currentPage, int $pageSize, string $searchTerm = '')
    {
        $repository = $this->doctrine->getRepository(SocialMedia::class);
        return $repository->paginatedSocialMedia($currentPage, $pageSize, $searchTerm);
    }

    /**
     * Get social media entities that match the search term (by name).
     * @param string $searchTerm
     * @return array
     */
    public function getSocialMediaByName(string $searchTerm)
    {
        $repository = $this->doctrine->getRepository(SocialMedia::class);
        return $repository->searchResultsSocialMedia($searchTerm);
    }
}
