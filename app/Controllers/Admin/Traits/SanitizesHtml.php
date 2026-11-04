<?php

namespace App\Controllers\Admin\Traits;

/**
 * Server-side rich-text HTML sanitization with a tag/attribute allowlist.
 *
 * Shared by Pages and Articles so both editors enforce the same policy:
 * only presentational markup survives, event handlers and unsafe URL
 * schemes (javascript:, vbscript:, data:) are stripped.
 */
trait SanitizesHtml
{
    protected array $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'a', 'img',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'hr', 'div', 'span',
        'iframe',
    ];

    protected array $allowedAttributes = [
        'a'      => ['href', 'title', 'target', 'rel'],
        'img'    => ['src', 'alt', 'title', 'width', 'height', 'style', 'class'],
        'td'     => ['colspan', 'rowspan'],
        'th'     => ['colspan', 'rowspan'],
        'div'    => ['class', 'style'],
        'span'   => ['class'],
        'p'      => ['class'],
        'table'  => ['class'],
        'iframe' => ['src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen', 'title', 'class', 'style'],
    ];

    protected array $allowedStyleProperties = [
        'width', 'max-width', 'height', 'display', 'margin',
        'margin-left', 'margin-right', 'float', 'text-align',
        'aspect-ratio', 'border',
        'position', 'padding-bottom', 'overflow', 'top', 'left',
    ];

    protected array $allowedImgClasses = [
        'img-fluid', 'img-align-left', 'img-align-center', 'img-align-right',
    ];

    protected array $allowedDivClasses = [
        'video-embed-wrapper', 'img-align-left', 'img-align-center', 'img-align-right',
    ];

    protected function sanitizeHtml(string $html): string
    {
        if (empty(trim($html))) {
            return '';
        }

        $allowedTagStr = implode('', array_map(fn($t) => "<{$t}>", $this->allowedTags));
        $html = strip_tags($html, $allowedTagStr);

        // Parse and filter attributes
        $html = preg_replace_callback(
            '/<(\w+)((?:\s+[^>]*)?)>/s',
            function ($matches) {
                $tag = strtolower($matches[1]);
                $attrStr = $matches[2];

                if (! in_array($tag, $this->allowedTags, true)) {
                    return '';
                }

                if (empty(trim($attrStr))) {
                    return "<{$tag}>";
                }

                $allowedForTag = $this->allowedAttributes[$tag] ?? [];
                if (empty($allowedForTag)) {
                    return "<{$tag}>";
                }

                $filteredAttrs = '';
                preg_match_all('/(\w[\w-]*)=(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $attrStr, $attrMatches, PREG_SET_ORDER);

                // Check for boolean attributes (like allowfullscreen)
                preg_match_all('/\s(\w[\w-]*)(?=\s|$|>)(?!=)/', $attrStr, $boolMatches);

                foreach ($attrMatches as $attr) {
                    $name  = strtolower($attr[1]);

                    // Determine which capture group holds the value by inspecting
                    // the first character after '=' in the full match. The
                    // alternation leaves unused groups as empty strings rather
                    // than unset, so isset()/empty-string checks alone are not
                    // enough to distinguish quoted from unquoted matches.
                    $quoteChar = '';
                    if (preg_match('/^\S+=(?s:(.))/s', $attr[0], $quoteMatch)) {
                        $quoteChar = $quoteMatch[1];
                    }

                    if ($quoteChar === '"') {
                        $value = $attr[2] ?? '';
                    } elseif ($quoteChar === "'") {
                        $value = $attr[3] ?? '';
                    } else {
                        $value = $attr[4] ?? '';
                    }

                    if (! in_array($name, $allowedForTag, true)) {
                        continue;
                    }

                    // Block javascript: URLs in href and src
                    if (in_array($name, ['href', 'src'], true)) {
                        $testVal = strtolower(trim(preg_replace('/[\x00-\x20]+/', '', $value)));
                        if (preg_match('/^(?:javascript|vbscript|data):/i', $testVal)) {
                            continue;
                        }
                    }

                    // Iframe src: only allow youtube-nocookie.com embeds
                    if ($tag === 'iframe' && $name === 'src') {
                        if (! preg_match('#^https://www\.youtube-nocookie\.com/embed/#', $value)) {
                            continue;
                        }
                    }

                    // Sanitize style attributes
                    if ($name === 'style') {
                        $value = $this->sanitizeStyle($value);
                        if ($value === '') {
                            continue;
                        }
                    }

                    // Sanitize img class
                    if ($tag === 'img' && $name === 'class') {
                        $value = $this->filterClasses($value, $this->allowedImgClasses);
                        if ($value === '') {
                            continue;
                        }
                    }

                    // Sanitize div class
                    if ($tag === 'div' && $name === 'class') {
                        $value = $this->filterClasses($value, $this->allowedDivClasses);
                        if ($value === '') {
                            continue;
                        }
                    }

                    $filteredAttrs .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                }

                // Handle boolean attributes (like allowfullscreen)
                if (! empty($boolMatches[1])) {
                    foreach ($boolMatches[1] as $boolAttr) {
                        $boolAttr = strtolower($boolAttr);
                        if (in_array($boolAttr, $allowedForTag, true)) {
                            $filteredAttrs .= ' ' . $boolAttr;
                        }
                    }
                }

                return "<{$tag}{$filteredAttrs}>";
            },
            $html
        );

        return $html;
    }

    /**
     * Filter a style attribute value to only allow safe CSS properties.
     */
    protected function sanitizeStyle(string $style): string
    {
        $declarations = array_filter(array_map('trim', explode(';', $style)));
        $safe = [];

        foreach ($declarations as $decl) {
            if (strpos($decl, ':') === false) {
                continue;
            }

            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);

            if (in_array($prop, $this->allowedStyleProperties, true)) {
                // Block expression(), url() and javascript in values
                $testVal = strtolower($val);
                if (preg_match('/expression\s*\(|url\s*\(|javascript:/i', $testVal)) {
                    continue;
                }
                $safe[] = $prop . ': ' . $val;
            }
        }

        return implode('; ', $safe);
    }

    /**
     * Filter class attribute to only keep allowed class names.
     */
    protected function filterClasses(string $classes, array $allowed): string
    {
        $parts = preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY);
        $filtered = array_filter($parts, fn($c) => in_array($c, $allowed, true));

        return implode(' ', $filtered);
    }
}
