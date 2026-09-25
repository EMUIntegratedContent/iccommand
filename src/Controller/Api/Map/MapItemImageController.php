<?php

namespace App\Controller\Api\Map;

use App\Service\ImageUploadValidator;
use App\Service\UserService;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Map\MapitemImage;
use App\Entity\Map\MapItem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MapItemImageController extends AbstractController
{
	private UserService $service;
	private SerializerInterface $serializer;
	private ManagerRegistry $doctrine;
	private EntityManagerInterface $em;
	private ImageUploadValidator $validator;
	private CacheManager $imageCache;

	public function __construct(UserService $service, SerializerInterface $serializer, ManagerRegistry $doctrine, EntityManagerInterface $em, ImageUploadValidator $validator, CacheManager $imageCache)
	{
		$this->service = $service;
		$this->serializer = $serializer;
		$this->doctrine = $doctrine;
		$this->em = $em;
		$this->validator = $validator;
		$this->imageCache = $imageCache;
	}

	/**
	 * Process new image uploads
	 */
	#[Route('/uploads', methods: ['POST'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN") or is_granted("ROLE_MAP_IMAGE_UPLOAD")'))]
	public function postMapitemimageUploadAction(Request $request): Response
	{
		$images = $request->files->all('uploadFiles');
		$processedImages = [];
		$errors = [];

		// Check the map item first so no image is stored without an owner.
		$mapItemId = $request->request->get('mapitem_id');
		if (!$mapItemId || !$this->doctrine->getRepository(MapItem::class)->find($mapItemId)) {
			return new Response(json_encode('The map item was not found.'), 404, ['Content-Type' => 'application/json']);
		}

		// Go through each uploaded image
		foreach ($images as $image) {
			$error = $this->validator->validate($image instanceof UploadedFile ? $image : null);
			if ($error !== null) {
				$errors[] = $error;
				continue;
			}
			$newImage = $this->storeImage($image); // store the image to the database
			$this->linkImageToMapItem($newImage, $mapItemId); // associate the new image with the map item
			$processedImages[] = $newImage;
		}
		// Same group as the map item GET, so new images have the same keys (e.g. "subdir") as loaded ones.
		$serialized = $this->serializer->serialize(['errors' => $errors, 'processedImages' => $processedImages], 'json', ['groups' => 'bldgs']);
		return new Response($serialized, 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Reorder the images for a map item
	 */
	#[Route('/reorder', methods: ['PUT'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN") or is_granted("ROLE_MAP_EDIT")'))]
	public function putMapitemimageReorderAction(Request $request): Response
	{
		$data = json_decode($request->getContent(), true);
		$idArray = $data['imageIds'] ?? [];

		for ($i = 0; $i < count($idArray); $i++) {
			$image = $this->doctrine->getRepository(MapitemImage::class)->find($idArray[$i]); // find the matching image

			// return an error if the image was not found
			if (!$image) {
				return new Response("An image was not found. Update was not executed.", 400, ['Content-Type' => 'application/json']);
			}
			$image->setPriority($i);
			$this->em->persist($image);
		}
		$this->em->flush(); // save the reordering of all images at once

		return new Response("Reorder saved successfully.", 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Rename a single image for a map item
	 */
	#[Route('/rename', methods: ['PUT'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN") or is_granted("ROLE_MAP_EDIT")'))]
	public function putMapitemimageRenameAction(Request $request): Response
	{
		// Decode JSON body directly — $request->get() was removed in Symfony 8,
		// and $request->request->get() rejects non-scalar values like the image object.
		$data = json_decode($request->getContent(), true);
		$imageArr = is_array($data) && is_array($data['image'] ?? null) ? $data['image'] : [];
		if (empty($imageArr['id']) || !isset($imageArr['name']) || trim((string) $imageArr['name']) === '') {
			return new Response(json_encode('An image id and a non-empty name are required.'), 422, ['Content-Type' => 'application/json']);
		}

		$image = $this->doctrine->getRepository(MapitemImage::class)->find($imageArr['id']); // find the matching image

		// return 404 if the image was not found
		if (!$image) {
			return new Response("An image was not found. Update was not executed.", 404, ['Content-Type' => 'application/json']);
		}

		$image->setName((string) $imageArr['name']);

		$this->em->persist($image);
		$this->em->flush(); // save the image

		return new Response("Image renamed to " . $image->getName() . ".", 200, ['Content-Type' => 'application/json']);
	}

	/**
	 * Delete a single image for a map item
	 */
	#[Route('/{id}', methods: ['DELETE'])]
	#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MAP_ADMIN") or is_granted("ROLE_MAP_DELETE")'))]
	public function deleteMapitemimageAction($id): Response
	{
		$image = $this->doctrine->getRepository(MapitemImage::class)->find($id); // find the matching image

		if (!$image) {
			return new Response("That image was not found. Deletion not executed.", 404, ['Content-Type' => 'application/json']);
		}
		// delete the image; Document::removeUpload unlinks the file
		$path = $image->getPath();
		$subDir = trim((string) $image->getSubDir(), '/');
		$this->em->remove($image);
		$this->em->flush();
		// and drop its cached thumbnails so it is no longer served
		if ($path) {
			$this->imageCache->remove($subDir . '/' . $path, 'squared_thumbnail');
		}

		return new Response("Image deleted successfully.", 204, ['Content-Type' => 'application/json']);
	}

	/**
	 * Store a validated image. Files are saved under a random name (see
	 * Document::preUpload), so two uploads with the same client filename no
	 * longer overwrite each other; the original filename is kept as the
	 * display name.
	 */
	protected function storeImage(UploadedFile $image): MapitemImage
	{
		$newImage = new MapitemImage();
		$newImage->setName($image->getClientOriginalName());
		$newImage->setFile($image);
		$newImage->setPriority(10000); // will be changed when associated with a map item
		$newImage->setSubDir($this->getParameter('mapitem_images_subdirectory'));

		$this->em->persist($newImage);
		$this->em->flush();

		return $newImage;
	}

	protected function linkImageToMapItem(MapitemImage $image, $itemId): bool
	{
		$mapItem = $this->doctrine->getRepository(MapItem::class)->find($itemId);
		if (!$mapItem) {
			return false;
		}
		$mapItem->addImage($image);

		$this->em->persist($mapItem);

		// Assign this image lowest priority among all associated images
		$numMapItemImages = $this->getCountMapItemImages($itemId);
		if ($numMapItemImages > -1) {
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
	protected function getCountMapItemImages($itemId): int
	{
		$mapItem = $this->doctrine->getRepository(MapItem::class)->find($itemId);
		if (!$mapItem) {
			return -1;
		}

		return count($mapItem->getImages());
	}

}
