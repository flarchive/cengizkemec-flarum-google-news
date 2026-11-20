<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api\Controller;

use Carbon\Carbon;
use Cengizkemec\GoogleNews\Services\CacheService;
use Cengizkemec\GoogleNews\Services\Settings;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StatusController implements RequestHandlerInterface
{
    public function __construct(
        protected ExtensionManager $extensions,
        protected Settings $settings,
        protected CacheService $cache,
        protected UrlGenerator $url
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $base = rtrim($this->url->to('forum')->base(), '/');
        $enabledExtensions = $this->extensions->getEnabled();
        $extension = $this->extensions->getExtension('cengizkemec-google-news');
        $blog = $this->extensions->getExtension('fof-blog');

        $caches = [];

        foreach ([
            'news_sitemap' => CacheService::NEWS_SITEMAP,
            'content_sitemap' => CacheService::CONTENT_SITEMAP,
            'rss_feed' => CacheService::RSS_FEED,
            'atom_feed' => CacheService::ATOM_FEED,
        ] as $name => $key) {
            $caches[$name] = $this->cacheStatus($key);
        }

        $lastRefresh = null;

        foreach ($caches as $cache) {
            if ($cache['built_at'] === null) {
                continue;
            }

            $date = Carbon::parse($cache['built_at']);

            if ($lastRefresh === null || $date->greaterThan($lastRefresh)) {
                $lastRefresh = $date;
            }
        }

        $seoExtensions = array_values(array_intersect($enabledExtensions, [
            'fof-seo',
            'fof-sitemap',
            'flarum-seo',
            'v17development-seo',
            'nearata-sitemap',
            'sitemap',
        ]));

        return new JsonResponse([
            'enabled' => $this->settings->isEnabled(),
            'fof_blog' => [
                'installed' => $blog !== null,
                'enabled' => in_array('fof-blog', $enabledExtensions, true),
                'integration_enabled' => $this->settings->fofBlogIntegration(),
                'version' => $blog?->getVersion(),
            ],
            'features' => [
                'news_sitemap' => $this->settings->bool('sitemap.enabled', true),
                'content_sitemap' => $this->settings->bool('sitemap.content_enabled', true),
                'rss' => $this->settings->bool('feeds.rss_enabled', true),
                'atom' => $this->settings->bool('feeds.atom_enabled', true),
                'structured_data' => $this->settings->schemaEnabled('news_article')
                    || $this->settings->schemaEnabled('blog_posting')
                    || $this->settings->schemaEnabled('article'),
            ],
            'cache' => [
                'store' => $this->cache->storeName(),
                'entries' => $caches,
                'last_refresh' => $lastRefresh?->toIso8601String(),
            ],
            'errors' => $this->cache->recentErrors(),
            'compatibility' => [
                'flarum_version' => Application::VERSION,
                'php_version' => PHP_VERSION,
                'extension_version' => $extension?->getVersion(),
                'seo_extensions' => $seoExtensions,
                'fof_seo_enabled' => in_array('fof-seo', $enabledExtensions, true),
                'compatibility_mode' => $this->settings->compatibilityMode(),
            ],
            'endpoints' => [
                'news_sitemap' => $base.'/news-sitemap.xml',
                'content_sitemap' => $base.'/google-news-sitemap.xml',
                'rss' => $base.'/news-feed.xml',
                'atom' => $base.'/news-atom.xml',
            ],
            'resources' => [
                'search_console' => 'https://search.google.com/search-console',
                'publisher_center' => 'https://publishercenter.google.com/',
                'news_sitemap_docs' => 'https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap',
            ],
        ]);
    }

    protected function cacheStatus(string $key): array
    {
        $payload = $this->cache->payload($key);
        $builtAt = $this->cache->cachedAt($key);
        $ttl = isset($payload['ttl']) ? (int) $payload['ttl'] : null;

        return [
            'cached' => $payload !== null,
            'built_at' => $builtAt?->toIso8601String(),
            'entries' => isset($payload['count']) ? (int) $payload['count'] : 0,
            'ttl' => $ttl,
            'stale' => $builtAt !== null && $ttl !== null
                ? $builtAt->copy()->addSeconds($ttl)->isPast()
                : null,
        ];
    }
}
