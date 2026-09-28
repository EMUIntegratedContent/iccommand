<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds baseline security headers to every response Symfony produces.
 *
 * - X-Frame-Options: DENY stops other sites framing ICCommand (clickjacking).
 * - X-Content-Type-Options: nosniff stops browsers guessing content types.
 * - Referrer-Policy limits what other sites learn from followed links.
 * - Strict-Transport-Security (production, HTTPS only) tells browsers to use
 *   HTTPS for a year. includeSubDomains is deliberately not set because it
 *   would apply to every *.emich.edu host.
 *
 * Headers a controller already set are left alone. A Content-Security-Policy
 * is not added yet: the templates still rely on inline scripts.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
	public function __construct(
		#[Autowire('%kernel.environment%')]
		private string $environment,
	) {
	}

	public static function getSubscribedEvents(): array
	{
		return [KernelEvents::RESPONSE => ['onKernelResponse', -10]];
	}

	public function onKernelResponse(ResponseEvent $event): void
	{
		if (!$event->isMainRequest()) {
			return;
		}

		$headers = $event->getResponse()->headers;
		$defaults = [
			'X-Frame-Options' => 'DENY',
			'X-Content-Type-Options' => 'nosniff',
			'Referrer-Policy' => 'strict-origin-when-cross-origin',
		];
		if ($this->environment === 'prod' && $event->getRequest()->isSecure()) {
			$defaults['Strict-Transport-Security'] = 'max-age=31536000';
		}

		foreach ($defaults as $name => $value) {
			if (!$headers->has($name)) {
				$headers->set($name, $value);
			}
		}
	}
}
