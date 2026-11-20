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

class AtomFeedController extends AbstractFeedController
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->settings->isEnabled() || ! $this->settings->bool('feeds.atom_enabled', true)) {
            throw new RouteNotFoundException();
        }

        $limit = $this->settings->feedLimit();
        $ttl = $this->settings->cacheDuration();

        $payload = $this->cache->remember(CacheService::ATOM_FEED, $ttl, function () use ($limit) {
            return $this->feedBuilder->atom(
                $this->repository->feedItems($limit),
                $this->publisher,
                $this->url->to('forum')->path('news-atom.xml')
            );
        });

        return $this->feedResponse($payload, 'application/atom+xml; charset=UTF-8');
    }
}
