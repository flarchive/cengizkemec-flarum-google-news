<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Support;

/**
 * URL helpers that only ever produce absolute HTTP(S) URLs.
 */
final class Url
{
    public static function absolute(?string $url, string $baseUrl): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x20]/', $url)) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            $url = rtrim($baseUrl, '/').$url;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    public static function join(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    public static function language(?string $language, string $fallback = 'en'): string
    {
        $language = trim((string) $language);

        if ($language === '') {
            $language = $fallback;
        }

        // Google News expects an ISO 639-1 language code. Preserve a valid
        // region subtag (e.g. pt-BR) but normalise the casing.
        if (preg_match('/^([a-zA-Z]{2})(?:[-_]([a-zA-Z]{2}))?$/', $language, $matches)) {
            return strtolower($matches[1]).(isset($matches[2]) ? '-'.strtoupper($matches[2]) : '');
        }

        return 'en';
    }
}
