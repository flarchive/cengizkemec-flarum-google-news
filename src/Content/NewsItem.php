<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Content;

use Carbon\Carbon;

/**
 * A provider-independent, already validated article representation.
 *
 * Builders never touch Eloquent models directly, which keeps sitemap, feed and
 * JSON-LD generation testable and free of N+1 queries.
 */
final class NewsItem
{
    public function __construct(
        public readonly int $discussionId,
        public readonly string $title,
        public readonly string $url,
        public readonly Carbon $publishedAt,
        public readonly Carbon $modifiedAt,
        public readonly string $language,
        public readonly string $schemaType,
        public readonly string $provider,
        public readonly bool $isBlog,
        public readonly bool $isNews,
        public readonly bool $structuredDataEnabled = true,
        public readonly ?string $description = null,
        public readonly ?string $authorName = null,
        public readonly ?string $authorUrl = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $category = null,
        public readonly ?string $categoryUrl = null,
        public readonly ?string $blogUrl = null,
        public readonly ?string $headline = null
    ) {
    }

    public function seoTitle(): string
    {
        return $this->headline !== null && $this->headline !== ''
            ? $this->headline
            : $this->title;
    }

    public function isValid(): bool
    {
        return $this->title !== ''
            && $this->url !== ''
            && filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }

    public function isEligibleForNewsSitemap(): bool
    {
        return $this->isNews
            && $this->isValid()
            && $this->publishedAt->greaterThanOrEqualTo(Carbon::now()->subHours(48));
    }
}
