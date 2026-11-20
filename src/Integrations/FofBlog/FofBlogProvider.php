<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Integrations\FofBlog;

use Cengizkemec\GoogleNews\Integrations\ContentProviderInterface;
use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\Settings;
use Cengizkemec\GoogleNews\Support\Url;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\SlugManager;

/**
 * Optional adapter for FriendsOfFlarum Blog (fof/blog).
 *
 * The adapter only uses public FoF Blog model relationships and convention:
 * Discussion::blogMeta and the /blog/{slug} route. It never assumes private
 * classes or database columns beyond blog_meta's documented model attributes.
 */
class FofBlogProvider implements ContentProviderInterface
{
    public function __construct(
        protected ExtensionManager $extensions,
        protected Settings $settings,
        protected SlugManager $slugManager,
        protected PublisherConfig $publisher
    ) {
    }

    public function id(): string
    {
        return 'fof-blog';
    }

    public function isAvailable(): bool
    {
        return $this->extensions->isEnabled('fof-blog')
            && $this->settings->fofBlogIntegration()
            && class_exists(\FoF\Blog\BlogMeta::class);
    }

    public function supports(Discussion $discussion): bool
    {
        if (! $this->isAvailable() || ! $discussion->isRelation('blogMeta')) {
            return false;
        }

        if ($discussion->relationLoaded('blogMeta')) {
            return $discussion->getRelation('blogMeta') !== null;
        }

        return $discussion->blogMeta()->exists();
    }

    public function isBlog(): bool
    {
        return true;
    }

    public function canonicalUrl(Discussion $discussion): string
    {
        $slug = $this->slugManager->forResource(Discussion::class)->toSlug($discussion);

        return Url::join($this->publisher->baseUrl(), 'blog/'.$slug);
    }

    public function summary(Discussion $discussion): ?string
    {
        $meta = $this->blogMeta($discussion);

        return $meta?->summary;
    }

    public function coverImage(Discussion $discussion): ?string
    {
        $image = $this->blogMeta($discussion)?->featured_image;

        return $image !== null ? Url::absolute($image, $this->publisher->baseUrl()) : null;
    }

    public function categories(Discussion $discussion): array
    {
        if (! $discussion->isRelation('tags') || ! $discussion->relationLoaded('tags')) {
            return [];
        }

        return $discussion->tags
            ->map(function ($tag) {
                return [
                    'name' => (string) $tag->name,
                    'url' => Url::join($this->publisher->baseUrl(), 'blog/category/'.$tag->slug),
                ];
            })
            ->values()
            ->all();
    }

    public function blogOverviewUrl(): ?string
    {
        return Url::join($this->publisher->baseUrl(), 'blog');
    }

    public function isPendingReview(Discussion $discussion): bool
    {
        return (bool) $this->blogMeta($discussion)?->is_pending_review;
    }

    protected function blogMeta(Discussion $discussion): ?object
    {
        if (! $discussion->isRelation('blogMeta')) {
            return null;
        }

        if ($discussion->relationLoaded('blogMeta')) {
            return $discussion->getRelation('blogMeta');
        }

        return $discussion->blogMeta()->first();
    }
}
