<?php
namespace App\Controller\Api\Map;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Map\MapitemImage;
use App\Entity\Map\MapItem;

class MapItemImageController extends AbstractFOSRestController{

  /**
   * Process new image uploads
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_IMAGE_UPLOAD')")
   */
  public function postMapitemimageUploadAction(Request $request) : Response
  {
    $images = $request->files->get('uploadFiles');
    $processedImages = array();
    $errors = array();

    // Go through each uploaded image
    foreach($images as $image){
      $newImage = $this->storeImage($image); // store the image to the database
      if($newImage){
        $this->linkImageToMapItem($newImage, $request->request->get('mapitem_id')); // associate the new image with the map item
        $processedImages[] = $newImage;
      } else {
        $errors[] = "The file " . $image->getClientOriginalName() . " was not uploaded because it either already exists or is not a JPG, PNG, or GIF.";
      }
    }
    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize(array('errors' => $errors, 'processedImages' => $processedImages), 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Reorder the images for a map item
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_EDIT')")
   */
  public function putMapitemimageReorderAction(Request $request) : Response
  {
    $idArray = $request->request->get('imageIds');

    $em = $this->getDoctrine()->getManager();

    for($i = 0; $i < count($idArray); $i++){
      $image = $this->getDoctrine()->getRepository(MapitemImage::class)->find($idArray[$i]); // find the matching image

      // return an error if the image was not found
      if(!$image){
        $response = new Response("An image was not found. Update was not executed.", 400, array('Content-Type' => 'application/json'));
        return $response;
      }
      $image->setPriority($i);
      $em->persist($image);
    }
    $em->flush(); // save the reordering of all images at once

    $response = new Response("Reorder saved successfully.", 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  /**
   * Rename a single image for a map item
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_EDIT')")
   */
  public function putMapitemimageRenameAction(Request $request) : Response
  {
    $imageArr = $request->request->get('image');

    $image = $this->getDoctrine()->getRepository(MapitemImage::class)->find($imageArr['id']); // find the matching image

    // return 404 if the image was not found
    if(!$image){
      $response = new Response("An image was not found. Update was not executed.", 404, array('Content-Type' => 'application/json'));
      return $response;
    }

    $image->setName($imageArr['name']);

    $em = $this->getDoctrine()->getManager();
    $em->persist($image);
    $em->flush(); // save the image

    $response = new Response("Image renamed to " . $image->getName() . ".", 200, array('Content-Type' => 'application/json'));
    return $response;
  }

  /**
   * Delete a single image for a map item
   *
   * @Security("has_role('ROLE_GLOBAL_ADMIN') or has_role('ROLE_MAP_DELETE')")
   */
  public function deleteMapitemimageAction($id) : Response
  {
    $image = $this->getDoctrine()->getRepository(MapitemImage::class)->find($id); // find the matching image

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

  protected function storeImage(UploadedFile $image) : ?MapitemImage
  {
    // Make sure user is uploading a JPG, PNG, or GIF and not
    if($this->isValidImage($image)){
      $existingImage = $this->getDoctrine()->getRepository(MapitemImage::class)->findOneBy(['path' => $image->getClientOriginalName()]);

      // only process images that don't match an existing image name
      if(!$existingImage){
        // save the image
        $newImage = new MapitemImage();
        $newImage->setName($image->getClientOriginalName());
        $newImage->setFile($image);
        $newImage->setPriority(10000); // will be changed when associated with a map item
        $newImage->setSubDir($this->container->getParameter('mapitem_images_subdirectory'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($newImage);
        $em->flush();

        return $newImage;
      }
    }

    return null;
  }

  protected function linkImageToMapItem(MapitemImage $image, $itemId) : bool
  {
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->find($itemId);
    if(!$mapItem){
      return false;
    }
    $mapItem->addImage($image);

    $em = $this->getDoctrine()->getManager();
    $em->persist($mapItem);

    // Assign this image lowest priority among all associated images
    $numMapItemImages = $this->getCountMapItemImages($itemId);
    if($numMapItemImages > -1){
      $image->setPriority($numMapItemImages - 1);
    } else {
      // first image, set as main (priority of 0)
      $image->setPriority(0);
    }
    $em->persist($mapItem);
    $em->flush();

    return true;
  }

  /**
   * Get a count of images associated with a map item.
   */
  protected function getCountMapItemImages($itemId) : int
  {
    $mapItem = $this->getDoctrine()->getRepository(MapItem::class)->find($itemId);
    if(!$mapItem){
      return -1;
    }

    return count($mapItem->getImages());
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
