<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api\Controller;

use Carbon\Carbon;
use Cengizkemec\GoogleNews\Content\NewsRepository;
use Cengizkemec\GoogleNews\Services\CacheService;
use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\Settings;
use Cengizkemec\GoogleNews\Services\SitemapBuilder;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\UrlGenerator;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NewsSitemapController implements RequestHandlerInterface
{
    public function __construct(
        protected Settings $settings,
        protected NewsRepository $repository,
        protected SitemapBuilder $builder,
        protected PublisherConfig $publisher,
        protected CacheService $cache,
        protected UrlGenerator $url
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->settings->isEnabled() || ! $this->settings->bool('sitemap.enabled', true)) {
            throw new RouteNotFoundException();
        }

        $max = $this->settings->maxNewsEntries();
        $ttl = min(300, $this->settings->cacheDuration());

        $payload = $this->cache->remember(CacheService::NEWS_SITEMAP, $ttl, function () use ($max) {
            return $this->builder->news($this->repository->newsItems($max), $this->publisher, $max);
        });

        return $this->xmlResponse($payload);
    }

    /**
     * @param array{content: string, built_at: string} $payload
     */
    protected function xmlResponse(array $payload): ResponseInterface
    {
        $response = new TextResponse($payload['content'], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        if (isset($payload['ttl'])) {
            $response = $response->withHeader('Cache-Control', 'public, max-age='.(int) $payload['ttl']);
        }

        if (isset($payload['built_at'])) {
            try {
                $response = $response->withHeader(
                    'Last-Modified',
                    Carbon::parse($payload['built_at'])->toRfc7231String()
                );
            } catch (\Throwable) {
                // A malformed cache timestamp must never break the sitemap.
            }
        }

        return $response;
    }
}
