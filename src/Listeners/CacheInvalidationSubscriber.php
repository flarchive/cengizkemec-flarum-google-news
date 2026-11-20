<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Listeners;

use Cengizkemec\GoogleNews\Services\CacheService;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Invalidates sitemap and feed caches when content changes.
 *
 * The listener is intentionally broad: stale caches are worse than an extra
 * regeneration, and regenerating is bounded by the configured entry limits.
 */
class CacheInvalidationSubscriber
{
    public function __construct(
        protected CacheService $cache
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $eventsToWatch = [
            \Flarum\Discussion\Event\Saving::class,
            \Flarum\Discussion\Event\Started::class,
            \Flarum\Discussion\Event\Deleted::class,
            \Flarum\Discussion\Event\Hidden::class,
            \Flarum\Discussion\Event\Restored::class,
            \Flarum\Discussion\Event\Renamed::class,
            \Flarum\Post\Event\Posted::class,
            \Flarum\Post\Event\Revised::class,
            \Flarum\Post\Event\Deleted::class,
            \Flarum\Post\Event\Hidden::class,
            \Flarum\Post\Event\Restored::class,
            \Flarum\Settings\Event\Saved::class,
            \FoF\Blog\Event\BlogMetaCreated::class,
            \FoF\Blog\Event\BlogMetaUpdated::class,
            \FoF\Blog\Event\ArticleApproved::class,
            \FoF\Blog\Event\ArticleFeatured::class,
            \FoF\Blog\Event\ArticleUnfeatured::class,
            \Flarum\Tags\Event\DiscussionWasTagged::class,
            \Flarum\User\Event\Renamed::class,
        ];

        $listener = function (): void {
            $this->cache->flush();
        };

        foreach ($eventsToWatch as $event) {
            if (class_exists($event)) {
                $events->listen($event, $listener);
            }
        }

        // Eloquent model events for our own metadata model and for core content.
        // Listening to the dispatcher (in addition to domain events) makes
        // invalidation reliable in long-running workers and console contexts.
        foreach ([
            \Cengizkemec\GoogleNews\Models\NewsMeta::class,
            \Flarum\Discussion\Discussion::class,
            \Flarum\Post\Post::class,
            \FoF\Blog\BlogMeta::class,
        ] as $model) {
            if (class_exists($model)) {
                $events->listen('eloquent.saved: '.$model, $listener);
                $events->listen('eloquent.deleted: '.$model, $listener);
            }
        }
    }
}
