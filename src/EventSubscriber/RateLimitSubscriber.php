<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Psr\Log\LoggerInterface;

class RateLimitSubscriber implements EventSubscriberInterface
{
	// PHP 8.x Constructor Promotion: cleaner and handles autowiring perfectly
	public function __construct(
		#[Target('limiter.external_redirects')]
		private RateLimiterFactory $externalRedirectsLimiter,
		private LoggerInterface $logger
	) {
	}

	public static function getSubscribedEvents(): array
	{
		return [
			RequestEvent::class => 'onKernelRequest',
		];
	}

	public function onKernelRequest(RequestEvent $event): void
	{
		if (!$event->isMainRequest()) {
			return;
		}

		$request = $event->getRequest();
		$userAgent = $request->headers->get('User-Agent', '');

		$botUserAgents = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'facebot', 'Applebot'];

		$routeName = $request->attributes->get('_route', '');

		if (in_array($userAgent, $botUserAgents) && str_contains($routeName, 'app_api_redirect_redirect_getexternalredirect')) {
			// Use Client IP as the unique key for the limiter
			$limiter = $this->externalRedirectsLimiter->create($request->getClientIp());
			$limit = $limiter->consume(1);

			if (!$limit->isAccepted()) {
				$this->logger->info("Rate limit exceeded for Bot: $userAgent from IP " . $request->getClientIp());

				$retryAfter = $limit->getRetryAfter()->getTimestamp() - time();
				$response = new Response(
					"Too many requests. Please try again after: " . $retryAfter . " seconds.",
					429,
					['Retry-After' => $retryAfter]
				);
				$event->setResponse($response);
			}
		}
	}
}
