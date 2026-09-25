<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Checks whether a redirect target works, following redirects to the final page.
 *
 * Requests to hosts other than www.emich.edu go through NoPrivateNetworkHttpClient, so an
 * editor can't make the server probe internal addresses. www.emich.edu is exempt because
 * campus DNS may resolve it to a private address.
 */
class LinkChecker
{
    public const OK = 'ok';
    public const NOT_FOUND = 'not_found';
    public const UNREACHABLE = 'unreachable';

    private const TRUSTED_HOSTS = ['www.emich.edu', 'emich.edu'];

    private const OPTIONS = [
        'timeout' => 5,
        'max_duration' => 10,
        'max_redirects' => 5,
        'headers' => ['User-Agent' => 'ICCommand link checker'],
    ];

    private HttpClientInterface $publicClient;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
        $this->publicClient = new NoPrivateNetworkHttpClient($client);
    }

    /**
     * @return string one of OK, NOT_FOUND, UNREACHABLE
     */
    public function check(string $url): string
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return self::UNREACHABLE;
        }
        $client = in_array($host, self::TRUSTED_HOSTS, true) ? $this->client : $this->publicClient;

        try {
            $status = $this->status($client, 'HEAD', $url);
            // Some servers don't support HEAD. Ask again with GET.
            if (in_array($status, [403, 405, 501], true)) {
                $status = $this->status($client, 'GET', $url);
            }
        } catch (ExceptionInterface $e) {
            $this->logger->info('Redirect target could not be reached', ['url' => $url, 'error' => $e->getMessage()]);

            return self::UNREACHABLE;
        }

        if ($status >= 300 && $status < 400) {
            return self::UNREACHABLE; // still redirecting after max_redirects: a loop or a very long chain
        }

        return $status === 404 || $status === 410 ? self::NOT_FOUND : self::OK;
    }

    private function status(HttpClientInterface $client, string $method, string $url): int
    {
        $response = $client->request($method, $url, self::OPTIONS);
        $status = $response->getStatusCode(); // final status, after redirects
        $response->cancel(); // don't download the body

        return $status;
    }
}
