<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Services;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Typed access to extension settings.
 *
 * Every key is namespaced with the extension id so that multiple Google News
 * related extensions can coexist without colliding.
 */
class Settings
{
    public const PREFIX = 'cengizkemec-google-news.';

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get(self::PREFIX.$key);

        return $value === null || $value === '' ? $default : $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        // Flarum stores an unchecked checkbox as an empty string, not as
        // null. Only a missing setting may fall back to the default value.
        $value = $this->settings->get(self::PREFIX.$key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function int(string $key, int $default, ?int $min = null, ?int $max = null): int
    {
        $value = (int) $this->get($key, $default);

        if ($min !== null) {
            $value = max($min, $value);
        }

        if ($max !== null) {
            $value = min($max, $value);
        }

        return $value;
    }

    public function string(string $key, string $default = ''): string
    {
        return trim((string) $this->get($key, $default));
    }

    public function isEnabled(): bool
    {
        return $this->bool('enabled', true);
    }

    public function fofBlogIntegration(): bool
    {
        return $this->bool('fof_blog_integration', true);
    }

    public function defaultLanguage(): ?string
    {
        $language = $this->string('default_language');

        return $language !== '' ? $language : null;
    }

    public function newsWindowHours(): int
    {
        // Google News sitemaps may only contain articles from the last 48 hours.
        return $this->int('sitemap.news_window_hours', 48, 1, 48);
    }

    public function maxNewsEntries(): int
    {
        return $this->int('sitemap.max_news_entries', 1000, 1, 1000);
    }

    public function contentMaxUrls(): int
    {
        return $this->int('sitemap.content_max_urls', 5000, 1, 50000);
    }

    public function cacheDuration(): int
    {
        return $this->int('sitemap.cache_duration', 900, 30, 86400);
    }

    public function feedLimit(): int
    {
        return $this->int('feeds.limit', 50, 1, 500);
    }

    public function feedsEnabled(): bool
    {
        return $this->bool('feeds.rss_enabled', true) || $this->bool('feeds.atom_enabled', true);
    }

    public function schemaEnabled(string $type): bool
    {
        return $this->bool('schema.'.$type, true);
    }

    public function seoEnabled(string $key): bool
    {
        return $this->bool('seo.'.$key, true);
    }

    public function compatibilityMode(): bool
    {
        return $this->bool('schema.compatibility_mode', true);
    }

    public function robots(): string
    {
        $value = $this->string('seo.robots', 'index,follow');

        return in_array($value, ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'], true)
            ? $value
            : 'index,follow';
    }

    public function openGraphType(): string
    {
        $value = $this->string('seo.open_graph_type', 'article');

        return in_array($value, ['article', 'website'], true) ? $value : 'article';
    }
}
