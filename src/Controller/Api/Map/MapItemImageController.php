<?php
namespace App\Controller\Api\Map;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use App\Entity\Image;
use App\Service\MapitemFileUploader;

class MapItemImageController extends FOSRestController{

  /**
   * Process images
   */
  public function postMapitemimageUploadAction(Request $request)
  {
    //$formData = $request->request->all();
    $images = $request->files->get('uploadFiles');

    $processedImages = array();
    foreach($images as $image){
      $newImage = $this->storeImage($image);
      if($newImage){
        $processedImages[] = $newImage;
      }
    }
    $serializer = $this->container->get('jms_serializer');
    $serialized = $serializer->serialize($processedImages, 'json');
    $response = new Response($serialized, 200, array('Content-Type' => 'application/json'));

    return $response;
  }

  protected function storeImage($image){
    $existingImage = $this->getDoctrine()->getRepository(Image::class)->findOneBy(['name' => $image->getClientOriginalName()]);

    // only process images that don't match an existing image name
    if(!$existingImage){
      // save the image
      $newImage = new Image();
      $newImage->setName($image->getClientOriginalName());
      $newImage->setFile($image);
      $newImage->setSubDir($this->container->getParameter('mapitem_images_subdirectory'));

      $em = $this->getDoctrine()->getManager();
      $em->persist($newImage);
      $em->flush();

      return $newImage;
    }
    return null;
  }
}
