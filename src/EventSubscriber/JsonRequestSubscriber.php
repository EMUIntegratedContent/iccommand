<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Replaces FOSRestBundle's body listener.
 * Decodes JSON request bodies and populates $request->request
 * so that $request->request->get() works for JSON payloads.
 */
class JsonRequestSubscriber implements EventSubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::REQUEST => ['onKernelRequest', 10],
		];
	}

	public function onKernelRequest(RequestEvent $event): void
	{
		$request = $event->getRequest();
		$contentType = $request->headers->get('Content-Type', '');

		if (str_contains($contentType, 'application/json') && $request->getContent()) {
			$data = json_decode($request->getContent(), true);
			if (is_array($data)) {
				$request->request->replace($data);
			}
		}
	}
}
