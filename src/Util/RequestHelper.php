<?php

namespace App\Util;

/**
 * Small stateless helpers for normalizing values pulled off a Request.
 * Keeps repetitive input massaging out of the controllers.
 */
final class RequestHelper
{
	/**
	 * Normalizes an optional request value so blank input is stored as NULL.
	 * Note that "0" is preserved -- only absent, empty, and whitespace-only values become NULL.
	 * @param mixed $value The raw request value.
	 * @return string|null The trimmed value, or null if it was absent or blank.
	 */
	public static function optionalString(mixed $value): ?string
	{
		if ($value === null) {
			return null;
		}

		$value = trim((string) $value);

		return $value === "" ? null : $value;
	}
}
