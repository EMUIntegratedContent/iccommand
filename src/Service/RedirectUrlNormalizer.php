<?php

namespace App\Service;

/**
 * Normalizes the "from" and "to" links of a redirect the same way for the form and the bulk upload.
 * Returns null when a link can't be parsed, so callers can reject it instead of crashing.
 */
class RedirectUrlNormalizer
{
    public const SITE = 'https://www.emich.edu';

    /**
     * An emich.edu URL becomes its path ("/foo"). A bare path gets a leading slash.
     * Other hosts are kept as they are.
     */
    public function normalizeFrom(?string $link): ?string
    {
        $link = str_replace(' ', '%20', trim((string) $link));
        $link = rtrim($link, '/');
        if ($link === '') {
            return null;
        }

        $parts = parse_url($link);
        if ($parts === false) {
            return null;
        }

        if (isset($parts['host'])) {
            if (!$this->isEmichHost($parts['host'])) {
                return $link;
            }
            if (!isset($parts['path']) || $parts['path'] === '') {
                return null; // the site root can't be a redirect source
            }

            return $this->withLeadingSlash($parts['path']);
        }

        return isset($parts['path']) ? $this->withLeadingSlash($parts['path']) : null;
    }

    /**
     * A www.emich.edu or emich.edu URL becomes its path. A bare path gets a leading slash.
     * Other hosts are kept, but only with an http or https scheme.
     */
    public function normalizeTo(?string $link): ?string
    {
        $link = rtrim((string) $link, '/');
        if (trim($link) === '') {
            return null;
        }

        $parts = parse_url($link);
        if ($parts === false) {
            return null;
        }

        if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['host'])) {
            if (!isset($parts['scheme'])) {
                return null; // "//host/path" is ambiguous
            }
            if (isset($parts['path']) && in_array(strtolower($parts['host']), ['www.emich.edu', 'emich.edu'], true)) {
                return $this->withLeadingSlash($parts['path']);
            }

            return $link;
        }

        if (isset($parts['scheme'])) {
            return null; // e.g. "https:foo" with no host
        }

        return isset($parts['path']) ? $this->withLeadingSlash($parts['path']) : null;
    }

    /**
     * The absolute URL to check for a normalized "to" link.
     */
    public function absoluteTo(string $toLink): string
    {
        return str_starts_with($toLink, '/') ? self::SITE . $toLink : $toLink;
    }

    /**
     * emich.edu or any subdomain of it. Hosts that merely contain "emich.edu" don't count.
     */
    public function isEmichHost(string $host): bool
    {
        $host = strtolower($host);

        return $host === 'emich.edu' || str_ends_with($host, '.emich.edu');
    }

    private function withLeadingSlash(string $path): string
    {
        return str_starts_with($path, '/') ? $path : '/' . $path;
    }
}
