<?php
namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserImage;
use App\Service\ImageUploadValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Profile images. Users manage only their own image; global admins may
 * delete anyone's.
 */
class UserImageController extends AbstractController{
    /** Liip filter sets that render profile images. */
    private const CACHE_FILTERS = ['profile', 'navbar_profile'];

    private EntityManagerInterface $em;
    private SerializerInterface $serializer;
    private ManagerRegistry $doctrine;
    private ImageUploadValidator $validator;
    private CacheManager $imageCache;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $em, ManagerRegistry $doctrine, ImageUploadValidator $validator, CacheManager $imageCache){
        $this->serializer = $serializer;
        $this->em = $em;
        $this->doctrine = $doctrine;
        $this->validator = $validator;
        $this->imageCache = $imageCache;
    }

  /**
   * Upload a new profile image for the current user, replacing any existing one.
   */
	#[Route('/uploads', name: 'user_image_upload', methods: ['POST'])]
	#[IsGranted('ROLE_USER')]
  public function postUserimageUploadAction(Request $request) : Response
  {
    /** @var User $user */
    $user = $this->getUser();
    $image = $request->files->get('uploadProfileImage');

    $error = $this->validator->validate($image);
    if ($error !== null) {
      return $this->jsonResult([$error], null);
    }

    $previous = $user->getImage();

    $newImage = new UserImage();
    $newImage->setName($user->getUsername());
    $newImage->setFile($image);
    $newImage->setSubDir($this->getParameter('user_images_subdirectory'));
    $this->em->persist($newImage);
    $this->em->flush();

    $user->setImage($newImage);
    $this->em->persist($user);
    $this->em->flush();

    // Remove the replaced image and its cached thumbnails.
    if ($previous !== null) {
      $this->removeImage($previous);
    }

    return $this->jsonResult([], $newImage);
  }

  /**
   * Delete a profile image. Only its owner or a global admin may do this.
   */
	#[Route('/{id}', methods: ['DELETE'])]
	#[IsGranted('ROLE_USER')]
  public function deleteUserimageAction($id) : Response
  {
    $image = $this->doctrine->getRepository(UserImage::class)->find($id);

    if(!$image){
      return new Response("That image was not found. Deletion not executed.", 404, array('Content-Type' => 'application/json'));
    }

    $owner = $this->doctrine->getRepository(User::class)->findOneBy(['image' => $image]);
    $isOwner = $owner !== null && $owner->getUserIdentifier() === $this->getUser()?->getUserIdentifier();
    if (!$isOwner && !$this->isGranted('ROLE_GLOBAL_ADMIN')) {
      throw $this->createAccessDeniedException('You may only delete your own profile image.');
    }

    $this->removeImage($image);

    return new Response("Image deleted successfully.", 204, array('Content-Type' => 'application/json'));
  }

  /**
   * Delete the image record (its file is unlinked by Document::removeUpload)
   * and any cached thumbnails.
   */
  private function removeImage(UserImage $image): void
  {
    $path = $image->getPath();
    $subDir = trim((string) $image->getSubDir(), '/');
    $this->em->remove($image);
    $this->em->flush();
    if ($path) {
      $this->imageCache->remove($subDir . '/' . $path, self::CACHE_FILTERS);
    }
  }

  private function jsonResult(array $errors, ?UserImage $processedImage): Response
  {
    $serialized = $this->serializer->serialize(array('errors' => $errors, 'processedImage' => $processedImage), 'json');
    return new Response($serialized, 200, array('Content-Type' => 'application/json'));
  }
}
