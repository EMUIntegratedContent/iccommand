<?php

namespace App\Tests\Security;

use App\Security\ExternalApiToken;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Regression guard for the Phase 0 fix (#130): every route that used to answer anonymously
 * (modernization review, Appendix A). For each guarded route: anonymous gets 401, a user of
 * an unrelated module gets 403, and the right role gets past authorization. The emich.edu
 * server-to-server routes need the shared token instead of a login (#142); their GETs stay public.
 *
 * "Gets past authorization" means any status except 401/403 and no 500: with an empty test
 * database and an empty body, a 404 or 400/422 from the controller is expected.
 */
class RouteAccessTest extends WebTestCase
{
    /** A role that grants nothing on any of these routes. */
    private const UNRELATED_ROLE = 'ROLE_MAP_ADMIN';

    /** @return array<string, array{string, string, string}> method, path, a role that may call it */
    public static function guardedRoutes(): array
    {
        return [
            'redirect delete' => ['DELETE', '/api/redirects/999999', 'ROLE_REDIRECT_USER'],
            'redirect list' => ['GET', '/api/redirects/list', 'ROLE_REDIRECT_USER'],
            'redirect search' => ['GET', '/api/redirects/search?searchterm=zz', 'ROLE_REDIRECT_USER'],
            'redirect get' => ['GET', '/api/redirects/999999', 'ROLE_REDIRECT_USER'],
            'redirect create' => ['POST', '/api/redirects/', 'ROLE_REDIRECT_USER'],
            'redirect update' => ['PUT', '/api/redirects/', 'ROLE_REDIRECT_USER'],
            'redirect upload' => ['POST', '/api/redirects/upload', 'ROLE_REDIRECT_USER'],
            'uncaught delete' => ['DELETE', '/api/uncaughts/999999', 'ROLE_REDIRECT_USER'],
            'uncaught list' => ['GET', '/api/uncaughts/', 'ROLE_REDIRECT_USER'],
            'uncaught update' => ['PUT', '/api/uncaughts/', 'ROLE_REDIRECT_USER'],
            'crime log upload' => ['POST', '/api/crimelog/upload', 'ROLE_CRIMELOG_USER'],
            'photo request create' => ['POST', '/api/photorequests/', 'ROLE_PHOTO_CREATE'],
            'directory search' => ['GET', '/api/directory/search?searchterm=zz', 'ROLE_DEPARTMENTS_VIEW'],
            'program keywords' => ['GET', '/api/programs/keywords', 'ROLE_PROGRAMS_VIEW'],
        ];
    }

    #[DataProvider('guardedRoutes')]
    public function testAnonymousGets401(string $method, string $path, string $role): void
    {
        $client = static::createClient();
        $this->send($client, $method, $path);

        $this->assertResponseStatusCodeSame(401);
    }

    #[DataProvider('guardedRoutes')]
    public function testUnrelatedRoleGets403(string $method, string $path, string $role): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::withRoles([self::UNRELATED_ROLE]));
        $this->send($client, $method, $path);

        $this->assertResponseStatusCodeSame(403);
    }

    #[DataProvider('guardedRoutes')]
    public function testRightRoleIsAllowed(string $method, string $path, string $role): void
    {
        $client = static::createClient();
        foreach ([$role, 'ROLE_GLOBAL_ADMIN'] as $allowed) {
            $client->loginUser(UserFactory::withRoles([$allowed]));
            $this->send($client, $method, $path);

            $this->assertPassedAuthorization($client, $allowed);
        }
    }

    public function testListRoutesReturn200ToTheRightRole(): void
    {
        $client = static::createClient();
        foreach ([
            '/api/redirects/list' => 'ROLE_REDIRECT_USER',
            '/api/uncaughts/' => 'ROLE_REDIRECT_USER',
            '/api/directory/search?searchterm=zz' => 'ROLE_DEPARTMENTS_VIEW',
            '/api/programs/keywords' => 'ROLE_PROGRAMS_VIEW',
        ] as $path => $role) {
            $client->loginUser(UserFactory::withRoles([$role]));
            $client->request('GET', $path);

            $this->assertSame(200, $client->getResponse()->getStatusCode(), $path);
        }
    }

    /** @return array<string, array{string}> */
    public static function publicRoutes(): array
    {
        return [
            'redirect lookup (emich.edu 404 page)' => ['/api/redirects/external/redirect?url=/zz-missing'],
            'uncaught lookup (emich.edu 404 page)' => ['/api/uncaughts/external/uncaught?url=/zz-missing'],
            'building names' => ['/api/directory/bldgnames'],
            'emergency banner' => ['/api/emergency/banner'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function testPublicRouteAnswersAnonymously(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        $this->assertPassedAuthorization($client, 'anonymous');
    }

    /** @return array<string, array{string, string}> */
    public static function tokenRoutes(): array
    {
        return [
            'redirect visit counter' => ['PUT', '/api/redirects/external/redirect'],
            'uncaught create' => ['POST', '/api/uncaughts/external/uncaught'],
            'uncaught visit counter' => ['PUT', '/api/uncaughts/external/uncaught'],
        ];
    }

    #[DataProvider('tokenRoutes')]
    public function testTokenRouteRejectsMissingOrWrongToken(string $method, string $path): void
    {
        $client = static::createClient();

        $this->send($client, $method, $path, ['url' => '/zz-missing']);
        $this->assertResponseStatusCodeSame(401);

        $this->send($client, $method, $path, ['url' => '/zz-missing'], 'wrong-token');
        $this->assertResponseStatusCodeSame(401);
    }

    #[DataProvider('tokenRoutes')]
    public function testTokenRouteAcceptsTheSharedToken(string $method, string $path): void
    {
        $client = static::createClient();
        $this->send($client, $method, $path, ['url' => '/zz-missing'], $_ENV['EXTERNAL_API_TOKEN']);

        $this->assertPassedAuthorization($client, 'shared token');
    }

    private function send(KernelBrowser $client, string $method, string $path, array $body = [], ?string $token = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if (null !== $token) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', ExternalApiToken::HEADER))] = $token;
        }
        $client->request($method, $path, [], [], $server, $body ? json_encode($body) : '{}');
    }

    private function assertPassedAuthorization(KernelBrowser $client, string $as): void
    {
        $status = $client->getResponse()->getStatusCode();
        $this->assertNotContains($status, [401, 403], "$as was refused");
        $this->assertLessThan(500, $status, "$as got a server error: ".substr((string) $client->getResponse()->getContent(), 0, 300));
    }
}
