<?php

namespace App\Libraries;

use App\Models\PageVisitModel;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Lightweight first-party visitor tracker.
 *
 * Privacy note: IP address and browser user-agent are recorded for basic
 * traffic and security monitoring. Records should be retained only as
 * long as operationally needed; use the `analytics:purge` CLI command to
 * remove old data on a schedule.
 *
 * This tracker does NOT:
 * - store cookies, session IDs, query strings, form data, or passwords
 * - send data to external services
 * - automatically block visitors based on IP
 */
class VisitorTracker
{
    /**
     * Record a public page visit.
     *
     * Called from public controllers AFTER confirming the content is
     * accessible (not a 404).  The method is a no-op when the visitor
     * is a logged-in admin or has an empty user-agent.
     *
     * @param string $pageType 'home', 'section', 'blog', 'article', or 'page'
     * @param int    $pageId   ID of the article/page row, or 0 for pages without a DB record
     * @param string $pagePath Canonical public path, e.g. /, /#about, /blog, /blog/slug, /pages/slug
     */
    public static function record(string $pageType, int $pageId, string $pagePath): void
    {
        // Skip if admin is logged in (previews, testing, etc.).
        if (session()->get('admin_id')) {
            return;
        }

        /** @var IncomingRequest $request */
        $request = service('request');

        $ua = mb_substr((string) $request->getUserAgent(), 0, 500);

        // Skip empty user-agents (automated health checks, curl without UA).
        if ($ua === '') {
            return;
        }

        $parsed = self::parseUserAgent($ua);

        // Skip obvious bots but still record them with is_bot = 1 for review.
        $isBot = $parsed['is_bot'] ? 1 : 0;

        $ip = $request->getIPAddress();

        $model = new PageVisitModel();
        $model->insert([
            'page_type'  => $pageType,
            'page_id'    => $pageId,
            'page_path'  => mb_substr($pagePath, 0, 500),
            'ip_address' => $ip,
            'user_agent' => $ua,
            'browser'    => $parsed['browser'],
            'os'         => $parsed['os'],
            'device_type'=> $parsed['device_type'],
            'is_bot'     => $isBot,
        ]);
    }

    /**
     * Parse a user-agent string into browser, OS, device type, and bot flag.
     *
     * Uses simple, well-known patterns — no third-party library needed.
     *
     * @return array{browser:string, os:string, device_type:string, is_bot:bool}
     */
    public static function parseUserAgent(string $ua): array
    {
        $browser    = self::detectBrowser($ua);
        $os         = self::detectOS($ua);
        $deviceType = self::detectDeviceType($ua);
        $isBot      = self::isBot($ua);

        return [
            'browser'     => $browser,
            'os'          => $os,
            'device_type' => $deviceType,
            'is_bot'      => $isBot,
        ];
    }

    private static function detectBrowser(string $ua): string
    {
        // Order matters: more specific patterns first.
        $browsers = [
            '/Edg(?:e|A|iOS)?\/(\d+)/i'         => 'Edge',
            '/OPR\/(\d+)/i'                      => 'Opera',
            '/Vivaldi\/(\d+)/i'                  => 'Vivaldi',
            '/Brave/i'                           => 'Brave',
            '/SamsungBrowser\/(\d+)/i'           => 'Samsung Browser',
            '/UCBrowser\/(\d+)/i'                => 'UC Browser',
            '/YaBrowser\/(\d+)/i'                => 'Yandex Browser',
            '/CriOS\/(\d+)/i'                    => 'Chrome',
            '/FxiOS\/(\d+)/i'                    => 'Firefox',
            '/Chrome\/(\d+)/i'                   => 'Chrome',
            '/Firefox\/(\d+)/i'                  => 'Firefox',
            '/Version\/(\d+).*Safari/i'          => 'Safari',
            '/Safari\/(\d+)/i'                   => 'Safari',
            '/MSIE\s(\d+)/i'                     => 'Internet Explorer',
            '/Trident\/.*rv:(\d+)/i'             => 'Internet Explorer',
        ];

        foreach ($browsers as $pattern => $name) {
            if (preg_match($pattern, $ua, $m)) {
                $version = $m[1] ?? '';
                return $version !== '' ? "{$name} {$version}" : $name;
            }
        }

        return 'Lainnya';
    }

    private static function detectOS(string $ua): string
    {
        $systems = [
            '/Windows NT 10/i'           => 'Windows 10+',
            '/Windows NT 6\.3/i'         => 'Windows 8.1',
            '/Windows NT 6\.2/i'         => 'Windows 8',
            '/Windows NT 6\.1/i'         => 'Windows 7',
            '/Windows/i'                 => 'Windows',
            '/iPad/i'                    => 'iPadOS',
            '/iPhone/i'                  => 'iOS',
            '/Macintosh/i'               => 'macOS',
            '/CrOS/i'                    => 'Chrome OS',
            '/Android\s?([\d.]+)?/i'     => 'Android',
            '/Linux/i'                   => 'Linux',
        ];

        foreach ($systems as $pattern => $name) {
            if (preg_match($pattern, $ua, $m)) {
                if ($name === 'Android' && ! empty($m[1])) {
                    return 'Android ' . $m[1];
                }
                return $name;
            }
        }

        return 'Lainnya';
    }

    private static function detectDeviceType(string $ua): string
    {
        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod|Opera Mini|IEMobile/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/Windows|Macintosh|Linux|CrOS/i', $ua)) {
            return 'desktop';
        }

        return 'other';
    }

    private static function isBot(string $ua): bool
    {
        $botPatterns = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
            'applebot', 'bingpreview', 'facebookexternalhit',
            'linkedinbot', 'twitterbot', 'whatsapp', 'telegrambot',
            'pingdom', 'uptimerobot', 'monitor', 'check_http',
            'curl', 'wget', 'python-requests', 'httpie', 'postman',
            'go-http-client', 'java/', 'libwww', 'lwp-trivial',
            'scrapy', 'headlesschrome', 'phantomjs', 'selenium',
        ];

        $lower = strtolower($ua);
        foreach ($botPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
