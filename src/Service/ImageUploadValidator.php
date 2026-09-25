<?php

namespace App\Service;

use App\Entity\Document;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Validates uploaded images before they are stored.
 *
 * Checks the detected content type against Document::ALLOWED_TYPES and
 * confirms the file actually decodes as an image of that type, so files that
 * merely start with an image header (e.g. a GIF/PHP polyglot) are rejected.
 */
class ImageUploadValidator
{
	/** 2 MB, the limit the upload forms already advertise. */
	public const MAX_BYTES = 2097152;

	/**
	 * @return string|null an error message, or null if the image is acceptable
	 */
	public function validate(?UploadedFile $file): ?string
	{
		if ($file === null) {
			return 'No file was uploaded.';
		}
		$name = $file->getClientOriginalName();
		if (!$file->isValid()) {
			return 'The file ' . $name . ' could not be uploaded.';
		}
		if ($file->getSize() > self::MAX_BYTES) {
			return 'The file ' . $name . ' is larger than 2 MB.';
		}

		$mimeType = $file->getMimeType();
		if (!isset(Document::ALLOWED_TYPES[$mimeType])) {
			return 'The file ' . $name . ' is not a JPG, PNG, or GIF.';
		}

		$info = @getimagesize($file->getPathname());
		if ($info === false || ($info['mime'] ?? null) !== $mimeType || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
			return 'The file ' . $name . ' is not a valid image.';
		}

		return null;
	}
}
