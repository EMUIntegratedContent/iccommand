<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Decides what an anonymous (or expired-session) request gets.
 *
 * Pages redirect to the login form, as before. API requests get a JSON 401 instead: a redirect
 * made axios receive the login page with status 200, so the Vue forms reported "saved" while
 * nothing was saved. The frontend sends the user to the login page on 401 (assets/js/bootstrap.js).
 */
class ApiAwareEntryPoint implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private const FIREWALL = 'main';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if (!$this->isApiRequest($request)) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        // After logging in, return to the page that made the call, never to the API URL itself.
        if ($request->hasSession()) {
            $session = $request->getSession();
            $page = $this->sameSitePage($request);
            $page !== null
                ? $this->saveTargetPath($session, self::FIREWALL, $page)
                : $this->removeTargetPath($session, self::FIREWALL);
        }

        return new JsonResponse(['error' => 'Authentication required. Please log in again.'], Response::HTTP_UNAUTHORIZED);
    }

    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/') || $request->getPathInfo() === '/api';
    }

    /**
     * The Referer, if it is a page on this site (not an API URL).
     */
    private function sameSitePage(Request $request): ?string
    {
        $referer = (string) $request->headers->get('Referer', '');
        $parts = parse_url($referer);
        if (!$parts || ($parts['host'] ?? null) !== $request->getHost()) {
            return null;
        }
        $path = $parts['path'] ?? '/';
        if (str_starts_with($path, '/api') || str_starts_with($path, '/login')) {
            return null;
        }

        return $referer;
    }
}
