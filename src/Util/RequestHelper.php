<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

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

	/** Largest page size a list endpoint will return (the UI offers up to 200). */
	public const int MAX_PAGE_SIZE = 200;

	/**
	 * Reads ?page= and ?limit= as integers and clamps them to safe ranges.
	 * Non-numeric input falls back to the defaults, so values can be passed
	 * straight into SQL LIMIT/OFFSET clauses without injection risk.
	 * @return array{0: int, 1: int} [page (>= 1), limit (1..$max)]
	 */
	public static function pagination(Request $request, int $defaultLimit, int $max = self::MAX_PAGE_SIZE): array
	{
		$page = filter_var($request->query->get('page'), FILTER_VALIDATE_INT);
		$limit = filter_var($request->query->get('limit'), FILTER_VALIDATE_INT);

		$page = ($page === false || $page < 1) ? 1 : $page;
		$limit = ($limit === false || $limit < 1) ? $defaultLimit : min($limit, $max);

		return [$page, $limit];
	}
}
