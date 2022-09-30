<?php
namespace App\Controller\Api\Map;

use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Map\MapitemImage;
use App\Entity\Map\MapItem;
use Symfony\Component\Serializer\SerializerInterface;

class MapItemImageController extends AbstractFOSRestController{

    private $service;
    private $serializer;
    private $doctrine;
    private $em;

    public function __construct(UserService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em){
        $this->service = $service;
        $this->serializer = $serializer;
        $this->doctrine = $doctrine;
        $this->em = $em;
    }
  /**
   * Process new image uploads
   * @Rest\Post("/uploads")
   * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MAP_IMAGE_UPLOAD')")
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
    $serialized = $this->serializer->serialize(array('errors' => $errors, 'processedImages' => $processedImages), 'json');
    return new Response($serialized, 200, array('Content-Type' => 'application/json'));
  }

  /**
   * Reorder the images for a map item
   * @Rest\Put(path="/reorder")
   * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MAP_EDIT')")
   */
  public function putMapitemimageReorderAction(Request $request) : Response
  {
    $idArray = $request->get('imageIds');

    for($i = 0; $i < count($idArray); $i++){
      $image = $this->doctrine->getRepository(MapitemImage::class)->find($idArray[$i]); // find the matching image

      // return an error if the image was not found
      if(!$image){
        return new Response("An image was not found. Update was not executed.", 400, array('Content-Type' => 'application/json'));
      }
      $image->setPriority($i);
      $this->em->persist($image);
    }
    $this->em->flush(); // save the reordering of all images at once

    return new Response("Reorder saved successfully.", 200, array('Content-Type' => 'application/json'));
  }

  /**
   * Rename a single image for a map item
   * @Rest\Put(path="/rename")
   * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MAP_EDIT')")
   */
  public function putMapitemimageRenameAction(Request $request) : Response
  {
    $imageArr = $request->get('image');

    $image = $this->doctrine->getRepository(MapitemImage::class)->find($imageArr['id']); // find the matching image

    // return 404 if the image was not found
    if(!$image){
      return new Response("An image was not found. Update was not executed.", 404, array('Content-Type' => 'application/json'));
    }

    $image->setName($imageArr['name']);

    $this->em->persist($image);
    $this->em->flush(); // save the image

    return new Response("Image renamed to " . $image->getName() . ".", 200, array('Content-Type' => 'application/json'));
  }

  /**
   * Delete a single image for a map item
   * @Rest\Delete(path="/{id}")
   * @Security("is_granted('ROLE_GLOBAL_ADMIN') or is_granted('ROLE_MAP_DELETE')")
   */
  public function deleteMapitemimageAction($id) : Response
  {
    $image = $this->doctrine->getRepository(MapitemImage::class)->find($id); // find the matching image

      if(!$image){
          return new Response("That image was not found. Deletion not executed.", 404, array('Content-Type' => 'application/json'));
      }
      // delete the image
      $this->em->remove($image);
      $this->em->flush();

      return new Response("Image deleted successfully.", 204, array('Content-Type' => 'application/json'));
  }

  protected function storeImage(UploadedFile $image) : ?MapitemImage
  {
    // Make sure user is uploading a JPG, PNG, or GIF and not
    if($this->isValidImage($image)){
      $existingImage = $this->doctrine->getRepository(MapitemImage::class)->findOneBy(['path' => $image->getClientOriginalName()]);
      // only process images that don't match an existing image name
      if(!$existingImage){
        // save the image
        $newImage = new MapitemImage();
        $newImage->setName($image->getClientOriginalName());
        $newImage->setFile($image);
        $newImage->setPriority(10000); // will be changed when associated with a map item
        $newImage->setSubDir($this->getParameter('mapitem_images_subdirectory'));

        $this->em->persist($newImage);
        $this->em->flush();

        return $newImage;
      }
    }

    return null;
  }

  protected function linkImageToMapItem(MapitemImage $image, $itemId) : bool
  {
    $mapItem = $this->doctrine->getRepository(MapItem::class)->find($itemId);
    if(!$mapItem){
      return false;
    }
    $mapItem->addImage($image);

    $this->em->persist($mapItem);

    // Assign this image lowest priority among all associated images
    $numMapItemImages = $this->getCountMapItemImages($itemId);
    if($numMapItemImages > -1){
      $image->setPriority($numMapItemImages - 1);
    } else {
      // first image, set as main (priority of 0)
      $image->setPriority(0);
    }
    $this->em->persist($mapItem);
    $this->em->flush();

    return true;
  }

  /**
   * Get a count of images associated with a map item.
   */
  protected function getCountMapItemImages($itemId) : int
  {
    $mapItem = $this->doctrine->getRepository(MapItem::class)->find($itemId);
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
    $fileSize = $image->getSize();

    // 2MB = 2097152 bytes
    return (($mimeType == 'image/jpeg' || $mimeType == 'image/png' || $mimeType == 'image/gif') && $fileSize <= 2097152);
  }
}
