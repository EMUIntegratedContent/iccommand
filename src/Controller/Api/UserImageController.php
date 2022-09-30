<?php
namespace App\Controller\Api;

use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\UserImage;
use App\Entity\User;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class UserImageController extends AbstractFOSRestController{
    private $em;
    private $serializer;
    private $doctrine;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $em, ManagerRegistry $doctrine){
        $this->serializer = $serializer;
        $this->em = $em;
        $this->doctrine = $doctrine;
    }

  /**
   * Process new image uploads
   * @Rest\Post(path="/uploads")
   * @Security("is_granted('ROLE_USER')")
   */
  public function postUserimageUploadAction(Request $request) : Response
  {
    $image = $request->files->get('uploadProfileImage');
    $processedImage = null;
    $errors = array();

    $newImage = $this->storeImage($image, $request->request->get('user_id')); // store the image to the database
    if($newImage){
      $this->linkImageToUser($newImage, $request->request->get('user_id')); // associate the new image with the map item
      $processedImage = $newImage;
    } else {
      $errors[] = "The file " . $image->getClientOriginalName() . " was not uploaded because it either already exists or is not a JPG, PNG, or GIF.";
    }

    $serialized = $this->serializer->serialize(array('errors' => $errors, 'processedImage' => $processedImage), 'json');
    return new Response($serialized, 200, array('Content-Type' => 'application/json'));
  }

  /**
   * Delete a user's profile image
   *
   * @Security("is_granted('ROLE_USER')")
   */
  public function deleteUserimageAction($id) : Response
  {
    $image = $this->doctrine->getRepository(UserImage::class)->findOneBy(['id' => $id]); // find the matching image

    if(!$image){
        return new Response("That image was not found. Deletion not executed.", 404, array('Content-Type' => 'application/json'));
    }
    // delete the image
    $this->em->remove($image);
    $this->em->flush();

    return new Response("Image deleted successfully.", 204, array('Content-Type' => 'application/json'));
  }

  /**
   * PROTECTED: Store a user's image
   */
  protected function storeImage(UploadedFile $image, $user_id) : ?UserImage
  {
    // Make sure user is uploading a JPG, PNG, or GIF and not
    if($this->isValidImage($image)){
      // fetch the user's name. It will be the name of the image.
      $user = $this->doctrine->getRepository(User::class)->find($user_id);
      if(!$user){
        return null;
      }
      $existingImage = $this->doctrine->getRepository(UserImage::class)->findOneBy(['name' => $image->getClientOriginalName()]);

      // only process images that don't match an existing image name
      if(!$existingImage){
        // save the image
        $newImage = new UserImage();
        $newImage->setName($user->getUsername());
        $newImage->setFile($image);
        $newImage->setSubDir($this->getParameter('user_images_subdirectory'));

        $this->em->persist($newImage);
        $this->em->flush();

        return $newImage;
      }
    }
    return null;
  }

  /**
   * PROTECTED: associate a new image with the appropriate user
   */
  protected function linkImageToUser(UserImage $image, $userId) : bool
  {
    $user = $this->doctrine->getRepository(User::class)->find($userId);
    if(!$user){
      return false;
    }
    $user->setImage($image);

    $this->em->persist($user);
    $this->em->flush();

    return true;
  }

  /**
   * Ensure image meets criteria for uploading
   */
  protected function isValidImage(UploadedFile $image) : bool {
    $mimeType = $image->getMimeType();
    $fileSize = $image->getSize();

    // 2MB = 2097152 bytes
    return (($mimeType == 'image/jpeg' || $mimeType == 'image/png' || $mimeType == 'image/gif') && $fileSize <= 2097152);
  }
}
