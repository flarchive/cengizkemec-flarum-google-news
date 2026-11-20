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
use Cengizkemec\GoogleNews\Services\FeedBuilder;
use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\Settings;
use Cengizkemec\GoogleNews\Services\SitemapBuilder;
use Flarum\Http\UrlGenerator;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractFeedController extends NewsSitemapController
{
    public function __construct(
        Settings $settings,
        NewsRepository $repository,
        SitemapBuilder $builder,
        PublisherConfig $publisher,
        CacheService $cache,
        UrlGenerator $url,
        protected FeedBuilder $feedBuilder
    ) {
        parent::__construct($settings, $repository, $builder, $publisher, $cache, $url);
    }

    /**
     * @param array{content: string, built_at: string} $payload
     */
    protected function feedResponse(array $payload, string $contentType): ResponseInterface
    {
        $response = new TextResponse($payload['content'], 200, [
            'Content-Type' => $contentType,
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
                // Ignore malformed cache timestamps.
            }
        }

        return $response;
    }
}
