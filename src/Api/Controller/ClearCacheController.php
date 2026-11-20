<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api\Controller;

use Cengizkemec\GoogleNews\Services\CacheService;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClearCacheController implements RequestHandlerInterface
{
    public function __construct(
        protected CacheService $cache
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $this->cache->flush();

        return new EmptyResponse(204);
    }
}
