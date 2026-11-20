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

class RssFeedController extends AbstractFeedController
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->settings->isEnabled() || ! $this->settings->bool('feeds.rss_enabled', true)) {
            throw new RouteNotFoundException();
        }

        $limit = $this->settings->feedLimit();
        $ttl = $this->settings->cacheDuration();

        $payload = $this->cache->remember(CacheService::RSS_FEED, $ttl, function () use ($limit) {
            return $this->feedBuilder->rss(
                $this->repository->feedItems($limit),
                $this->publisher,
                $this->url->to('forum')->path('news-feed.xml')
            );
        });

        return $this->feedResponse($payload, 'application/rss+xml; charset=UTF-8');
    }
}
