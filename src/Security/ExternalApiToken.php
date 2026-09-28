<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shared secret for server-to-server calls from emich.edu (the 404-page
 * helpers, the public photo-request form and the department search).
 *
 * emich.edu sends the secret in the X-API-Key header. The secret lives in the
 * EXTERNAL_API_TOKEN env var on both servers and never reaches a browser.
 *
 * EXTERNAL_API_TOKEN_MODE controls the rollout:
 *   - "log" (default): requests without a valid token are still accepted if
 *     they would have been accepted before this check existed, and a warning
 *     is logged so unknown callers show up before anything is blocked.
 *   - "enforce": only requests with a valid token are accepted.
 */
class ExternalApiToken
{
	public const HEADER = 'X-API-Key';

	public function __construct(
		#[Autowire(env: 'EXTERNAL_API_TOKEN')]
		private string $token,
		#[Autowire(env: 'EXTERNAL_API_TOKEN_MODE')]
		private string $mode,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Whether the request carries the configured secret. Always false when no
	 * secret is configured.
	 */
	public function isValid(Request $request): bool
	{
		$given = $request->headers->get(self::HEADER);

		return $this->token !== '' && is_string($given) && hash_equals($this->token, $given);
	}

	public function isEnforced(): bool
	{
		return strtolower(trim($this->mode)) === 'enforce';
	}

	/**
	 * Decide whether a server-to-server request may proceed.
	 *
	 * @param bool $acceptedWithoutToken whether this request would have been
	 *                                   accepted before the token existed; only
	 *                                   honoured in "log" mode
	 */
	public function allows(Request $request, bool $acceptedWithoutToken): bool
	{
		if ($this->isValid($request)) {
			return true;
		}

		$context = [
			'route' => $request->attributes->get('_route'),
			'path' => $request->getPathInfo(),
			'ip' => $request->getClientIp(),
			'user_agent' => $request->headers->get('User-Agent'),
			'header_present' => $request->headers->has(self::HEADER),
		];

		if ($this->isEnforced()) {
			$this->logger->warning('External API request rejected: missing or invalid API token.', $context);

			return false;
		}

		if ($acceptedWithoutToken) {
			$this->logger->warning('External API request accepted without a valid API token (EXTERNAL_API_TOKEN_MODE=log).', $context);

			return true;
		}

		return false;
	}
}
