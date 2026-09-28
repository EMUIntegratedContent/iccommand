<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Cleans HTML from the rich-text editor fields before it is stored.
 * See config/packages/html_sanitizer.yaml for what is allowed.
 */
class RichTextSanitizer
{
	public function __construct(
		#[Target('app.rich_text')]
		private HtmlSanitizerInterface $sanitizer,
	) {
	}

	/**
	 * @return string|null the sanitized HTML, or null if nothing is left
	 */
	public function sanitize(?string $html): ?string
	{
		if ($html === null) {
			return null;
		}
		$clean = trim($this->sanitizer->sanitize($html));

		return $clean === '' ? null : $clean;
	}
}
