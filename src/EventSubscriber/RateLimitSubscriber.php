<?php

/**
 * Created in April 2023, this subscriber prevents SEO bots from sending a bunch of requests through to the external redirects API.
 * It uses Symfony's RateLimiter and is registered in services.yaml
 */
namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class RateLimitSubscriber implements EventSubscriberInterface
{
    /**
     * @var RateLimiterFactory
     */
    private RateLimiterFactory $externalRedirectsLimiter;
    private LoggerInterface $logger;

    public function __construct(RateLimiterFactory $externalRedirectsLimiter, LoggerInterface $logger)
    {
        $this->externalRedirectsLimiter = $externalRedirectsLimiter;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void {
        try {
            // Retrieve the request from the request event
            $request = $event->getRequest();

            $userAgent = $request->headers->get('User-Agent');
            $botUserAgents = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'facebot', 'Applebot',
                //'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/111.0'
            ];

            // Check if the requested route name is the external redirect GET route's name and if the user agent is a known bot.
            if(in_array($userAgent, $botUserAgents) && str_contains($request->get("_route"), 'app_api_redirect_redirect_getexternalredirect')) {
                // Retrieve the limiter based on the request client IP
                $limiter = $this->externalRedirectsLimiter->create($request->getClientIp());
//              $limiter->reset();  // Use this to manually reset the limiter
                // Consume one request and check if it's still accepted
                $limiter = $limiter->consume(1);
                if (false === $limiter->isAccepted()) {
                    $retryAfter = $limiter->getRetryAfter();
                    $this->logger->info("Too many requests. User Agent: " . $request->headers->get('User-Agent') . " from IP " . $request->getClientIp());
                    throw new TooManyRequestsHttpException(json_encode($retryAfter));
                }
            }
        } catch (TooManyRequestsHttpException $e) {
            // Handle the exception here
            $retryAfter = $e->getHeaders()['Retry-After'];
            $response = new Response("Too many requests. Please try again after: " . $retryAfter, 429, ['Retry-After' => $retryAfter]);
            $event->setResponse($response);
        }
    }

}