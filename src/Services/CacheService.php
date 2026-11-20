<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Throwable;

/**
 * Caches generated sitemap/feed documents and exposes cache diagnostics.
 */
class CacheService
{
    public const NEWS_SITEMAP = 'cengizkemec-google-news.sitemap.news';
    public const CONTENT_SITEMAP = 'cengizkemec-google-news.sitemap.content';
    public const RSS_FEED = 'cengizkemec-google-news.feed.rss';
    public const ATOM_FEED = 'cengizkemec-google-news.feed.atom';
    public const ERRORS = 'cengizkemec-google-news.errors';

    public const DATA_KEYS = [
        self::NEWS_SITEMAP,
        self::CONTENT_SITEMAP,
        self::RSS_FEED,
        self::ATOM_FEED,
    ];

    public function __construct(
        protected CacheRepository $cache
    ) {
    }

    /**
     * @return array{content: string, built_at: string, count: int, ttl: int}
     */
    public function remember(string $key, int $ttl, callable $callback): array
    {
        $cached = $this->cache->get($key);

        if (is_array($cached) && isset($cached['content'], $cached['built_at'], $cached['count'])) {
            return $cached;
        }

        try {
            $payload = $callback();
        } catch (Throwable $exception) {
            $this->recordError($exception);

            throw $exception;
        }

        $payload = [
            'content' => (string) ($payload['content'] ?? $payload),
            'count' => (int) ($payload['count'] ?? 0),
            'built_at' => Carbon::now()->toIso8601String(),
            'ttl' => $ttl,
        ];

        $this->cache->put($key, $payload, $ttl);

        return $payload;
    }

    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }

    public function flush(): void
    {
        foreach (self::DATA_KEYS as $key) {
            $this->cache->forget($key);
        }
    }

    public function payload(string $key): ?array
    {
        $cached = $this->cache->get($key);

        return is_array($cached) ? $cached : null;
    }

    public function cachedAt(string $key): ?Carbon
    {
        $payload = $this->payload($key);

        if (! isset($payload['built_at'])) {
            return null;
        }

        try {
            return Carbon::parse($payload['built_at']);
        } catch (Throwable) {
            return null;
        }
    }

    public function recordError(Throwable $exception): void
    {
        $errors = $this->cache->get(self::ERRORS, []);
        $errors = is_array($errors) ? $errors : [];

        array_unshift($errors, [
            'time' => Carbon::now()->toIso8601String(),
            'message' => mb_substr($exception->getMessage(), 0, 500),
            'class' => $exception::class,
        ]);

        $this->cache->put(self::ERRORS, array_slice($errors, 0, 5), 86400);
    }

    /**
     * @return array<int, array{time: string, message: string, class: string}>
     */
    public function recentErrors(): array
    {
        $errors = $this->cache->get(self::ERRORS, []);

        return is_array($errors) ? $errors : [];
    }

    public function clearErrors(): void
    {
        $this->cache->forget(self::ERRORS);
    }

    public function storeName(): string
    {
        $store = $this->cache->getStore();

        return class_basename($store);
    }
}
