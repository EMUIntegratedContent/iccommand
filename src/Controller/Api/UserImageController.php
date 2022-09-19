<?php
namespace App\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\UserImage;
use App\Entity\OldFosUser;

class UserImageController extends AbstractFOSRestController{

  /**
   * Process new image uploads
   *
   * @Security("has_role('ROLE_USER')")
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

    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize(array('errors' => $errors, 'processedImage' => $processedImage), 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Delete a user's profile image
   *
   * @Security("has_role('ROLE_USER')")
   */
  public function deleteUserimageAction($id) : Response
  {
    $image = $this->getDoctrine()->getRepository(UserImage::class)->findOneBy(['id' => $id]); // find the matching image

    if(!$image){
        $response = new Response("That image was not found. Deletion not executed.", 404, array('Content-Type' => 'application/json'));
        return $response;
    }
    // delete the image
    $em = $this->getDoctrine()->getManager();
    $em->remove($image);
    $em->flush();

    $response = new Response("Image deleted successfully.", 204, array('Content-Type' => 'application/json'));
    return $response;
  }

  /**
   * PROTECTED: Store a user's image
   */
  protected function storeImage(UploadedFile $image, $user_id) : ?UserImage
  {
    // Make sure user is uploading a JPG, PNG, or GIF and not
    if($this->isValidImage($image)){
      // fetch the user's name. It will be the name of the image.
      $user = $this->getDoctrine()->getRepository(OldFosUser::class)->find($user_id);
      if(!$user){
        return null;
      }
      $existingImage = $this->getDoctrine()->getRepository(UserImage::class)->findOneBy(['name' => $image->getClientOriginalName()]);

      // only process images that don't match an existing image name
      if(!$existingImage){
        // save the image
        $newImage = new UserImage();
        $newImage->setName($user->getUsername());
        $newImage->setFile($image);
        $newImage->setSubDir($this->container->getParameter('user_images_subdirectory'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($newImage);
        $em->flush();

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
    $user = $this->getDoctrine()->getRepository(OldFosUser::class)->find($userId);
    if(!$user){
      return false;
    }
    $user->setImage($image);

    $em = $this->getDoctrine()->getManager();
    $em->persist($user);
    $em->flush();

    return true;
  }

  /**
   * Ensure image meets criteria for uploading
   */
  protected function isValidImage(UploadedFile $image) : bool {
    $mimeType = $image->getMimeType();
    $fileSize = $image->getClientSize();

    // 2MB = 2097152 bytes
    return (($mimeType == 'image/jpeg' || $mimeType == 'image/png' || $mimeType == 'image/gif') && $fileSize <= 2097152);
  }
}
