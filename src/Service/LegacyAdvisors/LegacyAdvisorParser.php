<?php

namespace App\Service\LegacyAdvisors;

use App\Entity\Ic\IcAdvisorType;

/**
 * Cleans and parses single values from the legacy advisor tables (advisor_programs,
 * uacdc_advisor_programs) for the one-time import into ic_advisors.
 */
final class LegacyAdvisorParser
{
    /** Relative legacy URLs are paths on this site. */
    public const SITE = 'https://www.emich.edu';

    /** Names that point somewhere else instead of naming a person or office. */
    private const SKIP_PATTERNS = [
        '/^(visit|contact|please)\b/i',
        '/(advising|advisor) information/i',
        '/^program advising$/i',
    ];

    private const ORGANIZATION_PATTERN = '/\b(College|Center|Services|Office|University|Department|Institute)\b/';

    /** A trailing degree or credential after a name, e.g. "Ph.D.", "Ed. S.", "(ASCP)". */
    private const CREDENTIAL_PATTERN = '/\s+(?:Ph\.?\s?D\.?|Ed\.?\s?D\.?|Ed\.?\s?S\.?|DMA|MHS|OTRL|MBA|CCC-A|MT|\(ASCP\))$/i';

    /**
     * Repairs UTF-8 that was read as cp1252 ("MÃ³nica" → "Mónica"), drops zero-width
     * characters, turns no-break spaces and tabs into spaces, collapses whitespace and trims.
     * Empty becomes null.
     */
    public static function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/[ÃÂâ][\x{0080}-\x{00BF}\x{0152}-\x{2122}]/u', $value)) {
            $repaired = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
            $lossless = mb_convert_encoding($repaired, 'UTF-8', 'Windows-1252') === $value;
            if ($lossless && mb_check_encoding($repaired, 'UTF-8')) {
                $value = $repaired;
            }
        }

        $value = str_replace(["\u{200B}", "\u{FEFF}", "\u{00A0}", "\t"], ['', '', ' ', ' '], $value);
        $value = trim(preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? null : $value;
    }

    /**
     * Whether a legacy advisor name is a person, an organization or neither (null: no record).
     */
    public static function classifyName(string $name): ?IcAdvisorType
    {
        foreach (self::SKIP_PATTERNS as $pattern) {
            if (preg_match($pattern, $name)) {
                return null;
            }
        }

        $beforeComma = explode(',', $name, 2)[0];

        return preg_match(self::ORGANIZATION_PATTERN, $beforeComma)
            ? IcAdvisorType::ORGANIZATION
            : IcAdvisorType::PERSON;
    }

    /**
     * Drops what follows the first comma and any trailing credentials, then splits off the
     * last word as the last name.
     *
     * @return array{first: string, last: string}
     */
    public static function splitPersonName(string $name): array
    {
        $base = trim(explode(',', $name, 2)[0]);
        while (preg_match(self::CREDENTIAL_PATTERN, $base) && str_contains(preg_replace(self::CREDENTIAL_PATTERN, '', $base), ' ')) {
            $base = trim(preg_replace(self::CREDENTIAL_PATTERN, '', $base));
        }

        $words = preg_split('/\s+/', $base);
        $last = array_pop($words);

        return ['first' => implode(' ', $words), 'last' => $last];
    }

    /**
     * Splits "319-M Porter" into its room and building text. A room written "206 J" becomes
     * "206J".
     *
     * @return array{room: ?string, building: ?string}
     */
    public static function parseAddress(?string $address): array
    {
        $address = self::cleanText($address);
        if ($address === null) {
            return ['room' => null, 'building' => null];
        }

        if (preg_match('/^(\d+ [A-Z](?= )|[A-Z]?\d+(?:-?[A-Z])?)\s+(.+)$/u', $address, $match)) {
            return ['room' => str_replace(' ', '', $match[1]), 'building' => $match[2]];
        }

        return ['room' => null, 'building' => $address];
    }

    public static function cleanEmail(?string $email): ?string
    {
        $email = self::cleanText($email);
        if ($email === null) {
            return null;
        }

        $email = mb_strtolower($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Ten-digit numbers become 734.487.1234. A value with no digits is not a phone number.
     */
    public static function cleanPhone(?string $phone): ?string
    {
        $phone = self::cleanText($phone);
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10) {
            return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6);
        }

        return $digits === '' ? null : $phone;
    }

    /**
     * Legacy URLs are site paths ("/cob/faculty/b_ross.php"), so they get the site in front.
     */
    public static function absoluteUrl(?string $url): ?string
    {
        $url = self::cleanText($url);
        if ($url === null) {
            return null;
        }
        if (str_starts_with($url, '/')) {
            return self::SITE . $url;
        }

        return preg_match('#^https?://#i', $url) ? $url : null;
    }

    /**
     * What two program names must share to be the same program: cleaned, straight quotes,
     * lowercase.
     */
    public static function programKey(string $name): string
    {
        $name = (string) self::cleanText($name);
        $name = str_replace(['’', '‘', '“', '”'], ["'", "'", '"', '"'], $name);

        return mb_strtolower($name);
    }

    /**
     * What two building names must share to be the same building: lowercase, hyphens as
     * spaces, without the words "building" and "hall".
     */
    public static function buildingKey(string $name): string
    {
        $name = mb_strtolower((string) self::cleanText($name));
        $name = str_replace('-', ' ', $name);
        $name = preg_replace('/\b(building|hall)\b/u', '', $name);

        return trim(preg_replace('/\s+/u', ' ', $name));
    }
}
