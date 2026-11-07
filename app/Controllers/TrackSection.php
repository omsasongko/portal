<?php

namespace App\Controllers;

use App\Libraries\VisitorTracker;

/**
 * Lightweight endpoint for client-side section-view tracking.
 *
 * When the homepage uses hash-based navigation (e.g. /#about, /#portfolio),
 * clicking a section link does NOT trigger a full page load. This endpoint
 * accepts a small POST from the homepage JS to record the section view.
 *
 * Only allowed section IDs are accepted; everything else is silently
 * ignored. No query strings, form data, or sensitive information are stored.
 */
class TrackSection extends BaseController
{
    /** Allowed homepage section IDs (whitelist). */
    private const ALLOWED_SECTIONS = [
        'home', 'about', 'services', 'portfolio',
        'testimonials', 'blog', 'contact',
    ];

    public function record()
    {
        // sendBeacon sends with Content-Type: application/json when
        // using a Blob.  XHR/fetch may use form data.  Accept both.
        $section = $this->request->getPost('section');

        if ($section === null) {
            $body = (string) $this->request->getBody();
            $json = json_decode($body, true);
            $section = $json['section'] ?? null;
        }

        if (! is_string($section)) {
            return $this->response->setStatusCode(400)->setBody('');
        }

        $section = strtolower(trim($section));

        if (! in_array($section, self::ALLOWED_SECTIONS, true)) {
            return $this->response->setStatusCode(400)->setBody('');
        }

        $pagePath = '/#' . $section;

        VisitorTracker::record('section', 0, $pagePath);

        return $this->response
            ->setStatusCode(204)
            ->setHeader('Cache-Control', 'no-store');
    }
}
