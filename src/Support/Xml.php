<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Support;

/**
 * XML escaping that is safe for both elements and attribute values.
 */
final class Xml
{
    public static function escape(?string $value): string
    {
        // Normalise invalid UTF-8 first. mb_convert_encoding() performs a
        // lossy conversion rather than throwing, so malformed user input can
        // never make a public sitemap or feed fail to render.
        $value = mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8');

        // Strip characters that are illegal in XML 1.0.
        $value = preg_replace(
            '/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            $value
        ) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function cdata(?string $value): string
    {
        $value = str_replace(']]>', ']]]]><![CDATA[>', (string) $value);

        return '<![CDATA['.$value.']]>';
    }

    public static function declaration(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    }

    public static function isoDate(?\DateTimeInterface $date): ?string
    {
        return $date?->format('c');
    }

    public static function rfc2822(?\DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_RSS);
    }
}
