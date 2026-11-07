<?php

namespace App\Libraries;

/**
 * Local-only IP geolocation using a bundled MMDB database.
 *
 * Privacy: IP addresses are resolved locally against the on-disk
 * database file.  No visitor data is sent to any external service.
 *
 * Accuracy: IP-based geolocation is inherently approximate.  Results
 * may be inaccurate when visitors use VPNs, mobile carriers, corporate
 * proxies, or satellite internet.  Results should always be labelled
 * as "approximate" in the UI.
 *
 * Data source: DB-IP Lite City database (MMDB format).
 * License: Creative Commons Attribution 4.0 (CC-BY-4.0).
 * https://db-ip.com/db/lite.php
 */
class GeoIpLookup
{
    private static ?MmdbReader $reader = null;

    /** Default MMDB path inside writable/. */
    private const MMDB_PATH = WRITEPATH . 'dbip-city-lite.mmdb';

    /**
     * IPv4/IPv6 ranges that are private, loopback, or link-local.
     * We check these first to avoid useless database lookups and to
     * return a meaningful label instead of "Unknown".
     */
    private const PRIVATE_RANGES = [
        '127.',            // IPv4 loopback
        '10.',             // RFC 1918
        '172.16.',         // RFC 1918 (172.16.0.0 – 172.31.255.255)
        '172.17.', '172.18.', '172.19.', '172.20.',
        '172.21.', '172.22.', '172.23.', '172.24.',
        '172.25.', '172.26.', '172.27.', '172.28.',
        '172.29.', '172.30.', '172.31.',
        '192.168.',        // RFC 1918
        '169.254.',        // Link-local
        '0.',              // "This" network
        '::1',             // IPv6 loopback
        'fc',              // IPv6 unique local (fc00::/7)
        'fd',              // IPv6 unique local
        'fe80:',           // IPv6 link-local
    ];

    /**
     * Look up an IP address and return location components.
     *
     * @return array{country: string, region: string, city: string, is_private: bool}
     */
    public static function lookup(string $ip): array
    {
        $unknown = [
            'country'    => '',
            'region'     => '',
            'city'       => '',
            'is_private' => false,
        ];

        $ip = trim($ip);
        if ($ip === '') {
            return $unknown;
        }

        // Check for private/loopback addresses first.
        if (self::isPrivateIp($ip)) {
            return [
                'country'    => 'Lokal/Jaringan Privat',
                'region'     => '',
                'city'       => '',
                'is_private' => true,
            ];
        }

        // Try the local MMDB database.
        try {
            $reader = self::getReader();
            if ($reader === null) {
                return $unknown;
            }

            $record = $reader->lookup($ip);
            if ($record === null) {
                return $unknown;
            }

            return [
                'country'    => self::extractName($record, 'country') ?: self::extractCountryIso($record),
                'region'     => self::extractSubdivision($record),
                'city'       => self::extractName($record, 'city'),
                'is_private' => false,
            ];
        } catch (\Throwable $e) {
            log_message('warning', '[GeoIpLookup] Lookup failed for IP {ip}: {error}', [
                'ip'    => $ip,
                'error' => $e->getMessage(),
            ]);
            return $unknown;
        }
    }

    /**
     * Format location for display: "City, Region, Country" or subsets.
     */
    public static function formatLocation(array $geo): string
    {
        if ($geo['is_private']) {
            return 'Lokal/Jaringan Privat';
        }

        $parts = array_filter([
            $geo['city'],
            $geo['region'],
            $geo['country'],
        ], fn($v) => $v !== '' && $v !== null);

        return $parts ? implode(', ', $parts) : 'Tidak Diketahui';
    }

    /**
     * Check whether an IP address is private, loopback, or link-local.
     */
    private static function isPrivateIp(string $ip): bool
    {
        $lower = strtolower($ip);
        foreach (self::PRIVATE_RANGES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }

        // Also use PHP's built-in filter as a fallback.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get or create the MMDB reader singleton.
     */
    private static function getReader(): ?MmdbReader
    {
        if (self::$reader !== null) {
            return self::$reader;
        }

        $path = self::MMDB_PATH;
        if (! is_readable($path)) {
            log_message('warning', '[GeoIpLookup] MMDB file not found at {path}', ['path' => $path]);
            return null;
        }

        self::$reader = new MmdbReader($path);
        return self::$reader;
    }

    /**
     * Extract a localized name from a record section.
     * Tries English first, then falls back to any available name.
     */
    private static function extractName(array $record, string $key): string
    {
        if (! isset($record[$key]) || ! is_array($record[$key])) {
            return '';
        }

        $names = $record[$key]['names'] ?? $record[$key];

        if (is_array($names)) {
            return (string) ($names['en'] ?? $names['name'] ?? reset($names) ?: '');
        }

        return '';
    }

    /**
     * Extract the ISO country code as fallback.
     */
    private static function extractCountryIso(array $record): string
    {
        return (string) ($record['country']['iso_code'] ?? '');
    }

    /**
     * Extract the first subdivision (region/province) name.
     */
    private static function extractSubdivision(array $record): string
    {
        if (! isset($record['subdivisions']) || ! is_array($record['subdivisions'])) {
            return '';
        }

        $first = reset($record['subdivisions']);
        if (is_array($first) && isset($first['names'])) {
            return (string) ($first['names']['en'] ?? reset($first['names']) ?: '');
        }

        return '';
    }
}
