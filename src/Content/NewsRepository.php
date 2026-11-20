<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Content;

use Carbon\Carbon;
use Cengizkemec\GoogleNews\Integrations\ContentProviderInterface;
use Cengizkemec\GoogleNews\Integrations\ProviderRegistry;
use Cengizkemec\GoogleNews\Models\NewsMeta;
use Cengizkemec\GoogleNews\Services\Settings;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\User\Guest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reads only public, published discussions. It never loads the whole forum:
 * every query is filtered, ordered and limited at the database level.
 */
class NewsRepository
{
    public function __construct(
        protected ProviderRegistry $providers,
        protected NewsItemFactory $factory,
        protected Settings $settings,
        protected ExtensionManager $extensions
    ) {
    }

    /**
     * @return NewsItem[]
     */
    public function newsItems(int $limit): array
    {
        $cutoff = Carbon::now()->subHours($this->settings->newsWindowHours());

        $discussions = $this->baseQuery()
            ->where('created_at', '>=', $cutoff)
            ->whereHas('googleNewsMeta', function (Builder $query) {
                $query->where('is_news', true)->where('include_in_sitemap', true);
            })
            ->limit(min(2000, $limit * 2))
            ->get();

        return $this->map($discussions, $limit, function (NewsItem $item, ?NewsMeta $meta) use ($cutoff) {
            return $item->isNews
                && $meta !== null
                && $meta->include_in_sitemap
                && $item->publishedAt->greaterThanOrEqualTo($cutoff);
        });
    }

    /**
     * Public blog posts and published news articles, for the optional general
     * content sitemap.
     *
     * @return NewsItem[]
     */
    public function contentItems(int $limit): array
    {
        $query = $this->baseQuery();

        if ($this->blogAvailable()) {
            $query->where(function (Builder $query) {
                $query->whereHas('googleNewsMeta')
                    ->orWhereHas('blogMeta');
            });
        } else {
            $query->whereHas('googleNewsMeta');
        }

        $discussions = $query
            ->limit(min(50000, $limit * 2))
            ->get();

        return $this->map($discussions, $limit, function (NewsItem $item) {
            return $item->isBlog || $item->isNews;
        });
    }

    /**
     * @return NewsItem[]
     */
    public function feedItems(int $limit): array
    {
        $query = $this->baseQuery();

        if ($this->blogAvailable()) {
            $query->where(function (Builder $query) {
                $query->whereHas('googleNewsMeta')
                    ->orWhereHas('blogMeta');
            });
        } else {
            $query->whereHas('googleNewsMeta');
        }

        $discussions = $query
            ->limit(min(1000, $limit * 2))
            ->get();

        return $this->map($discussions, $limit, function (NewsItem $item, ?NewsMeta $meta, ContentProviderInterface $provider) {
            if ($meta !== null && ! $meta->include_in_feed) {
                return false;
            }

            if ($item->isNews) {
                return true;
            }

            return $provider->isBlog() && $this->settings->bool('feeds.include_regular_posts', true);
        });
    }

    public function forDiscussion(Discussion $discussion): ?NewsItem
    {
        $this->loadRelations($discussion);

        if (! $this->isPublished($discussion)) {
            return null;
        }

        $provider = $this->providers->for($discussion);
        $meta = $discussion->googleNewsMeta;

        if ($provider === null || ($meta === null && ! $provider->isBlog())) {
            return null;
        }

        return $this->factory->make($discussion, $meta, $provider);
    }

    protected function baseQuery(): Builder
    {
        $query = Discussion::query()
            ->whereVisibleTo(new Guest())
            ->whereNull('hidden_at')
            ->where('is_private', false)
            ->whereHas('firstPost', function (Builder $query) {
                $query->whereNull('hidden_at')->where('is_private', false);
            })
            ->with([
                'firstPost.user',
                'googleNewsMeta',
            ])
            ->orderByDesc('created_at');

        if ($this->blogAvailable()) {
            $query->with('blogMeta')
                ->whereDoesntHave('blogMeta', function (Builder $query) {
                    $query->where('is_pending_review', true);
                });

            if ($this->tagsAvailable()) {
                $query->with('tags');
            }
        }

        return $query;
    }

    protected function loadRelations(Discussion $discussion): void
    {
        $relations = ['firstPost.user', 'googleNewsMeta'];

        if ($this->blogAvailable()) {
            $relations[] = 'blogMeta';
        }

        if ($this->tagsAvailable()) {
            $relations[] = 'tags';
        }

        $discussion->loadMissing($relations);
    }

    protected function isPublished(Discussion $discussion): bool
    {
        if ($discussion->hidden_at !== null || $discussion->is_private) {
            return false;
        }

        $firstPost = $discussion->firstPost;

        if ($firstPost === null || $firstPost->hidden_at !== null || $firstPost->is_private) {
            return false;
        }

        if ($this->blogAvailable() && $discussion->relationLoaded('blogMeta')) {
            $blogMeta = $discussion->getRelation('blogMeta');

            if ($blogMeta !== null && $blogMeta->is_pending_review) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Collection<int, Discussion> $discussions
     * @return NewsItem[]
     */
    protected function map(Collection $discussions, int $limit, callable $filter): array
    {
        $items = [];
        $seen = [];

        foreach ($discussions as $discussion) {
            if (! $this->isPublished($discussion)) {
                continue;
            }

            $provider = $this->providers->for($discussion);

            if ($provider === null) {
                continue;
            }

            $meta = $discussion->googleNewsMeta;
            $item = $this->factory->make($discussion, $meta, $provider);

            if ($item === null || ! $item->isValid() || isset($seen[$item->url])) {
                continue;
            }

            if (! $filter($item, $meta, $provider)) {
                continue;
            }

            $seen[$item->url] = true;
            $items[] = $item;

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    protected function blogAvailable(): bool
    {
        return $this->extensions->isEnabled('fof-blog') && class_exists(\FoF\Blog\BlogMeta::class);
    }

    protected function tagsAvailable(): bool
    {
        return $this->extensions->isEnabled('flarum-tags') && class_exists(\Flarum\Tags\Tag::class);
    }
}
