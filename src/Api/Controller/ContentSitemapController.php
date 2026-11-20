<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api\Controller;

use Cengizkemec\GoogleNews\Services\CacheService;
use Flarum\Http\Exception\RouteNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Separate, non-conflicting general content sitemap.
 *
 * Existing sitemap extensions keep their own /sitemap.xml. Administrators can
 * include this URL in a sitemap index if they want it crawled.
 */
class ContentSitemapController extends NewsSitemapController
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->settings->isEnabled() || ! $this->settings->bool('sitemap.content_enabled', true)) {
            throw new RouteNotFoundException();
        }

        $max = $this->settings->contentMaxUrls();
        $ttl = $this->settings->cacheDuration();

        $payload = $this->cache->remember(CacheService::CONTENT_SITEMAP, $ttl, function () use ($max) {
            return $this->builder->content($this->repository->contentItems($max), $max);
        });

        return $this->xmlResponse($payload);
    }
}
