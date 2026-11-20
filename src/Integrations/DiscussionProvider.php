<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Integrations;

use Cengizkemec\GoogleNews\Support\Url;
use Flarum\Discussion\Discussion;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;

/**
 * Fallback provider for any public discussion explicitly marked as news in the
 * Google News settings modal. It is always available, so the extension works
 * without FoF Blog.
 */
class DiscussionProvider implements ContentProviderInterface
{
    public function __construct(
        protected UrlGenerator $url,
        protected SlugManager $slugManager
    ) {
    }

    public function id(): string
    {
        return 'discussion';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function supports(Discussion $discussion): bool
    {
        return true;
    }

    public function isBlog(): bool
    {
        return false;
    }

    public function canonicalUrl(Discussion $discussion): string
    {
        $base = rtrim($this->url->to('forum')->base(), '/');
        $slug = $this->slugManager->forResource(Discussion::class)->toSlug($discussion);

        return Url::join($base, 'd/'.$slug);
    }

    public function summary(Discussion $discussion): ?string
    {
        return $discussion->firstPost?->content;
    }

    public function coverImage(Discussion $discussion): ?string
    {
        return null;
    }

    public function categories(Discussion $discussion): array
    {
        return [];
    }

    public function blogOverviewUrl(): ?string
    {
        return null;
    }
}
