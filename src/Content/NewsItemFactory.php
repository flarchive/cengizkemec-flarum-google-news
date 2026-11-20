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
use Cengizkemec\GoogleNews\Models\NewsMeta;
use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\Settings;
use Cengizkemec\GoogleNews\Support\Url;
use Flarum\Discussion\Discussion;

/**
 * Converts a discussion into a validated, provider-independent NewsItem.
 */
class NewsItemFactory
{
    public function __construct(
        protected Settings $settings,
        protected PublisherConfig $publisher
    ) {
    }

    public function make(
        Discussion $discussion,
        ?NewsMeta $meta,
        ContentProviderInterface $provider
    ): ?NewsItem {
        $title = $this->cleanText($meta?->headline ?: $discussion->title);

        if ($title === '') {
            return null;
        }

        $headline = $this->cleanText($meta?->headline);
        $description = $this->cleanText($meta?->description)
            ?: $this->excerpt($provider->summary($discussion))
            ?: $this->excerpt($discussion->firstPost?->content);

        $authorName = $this->cleanText($meta?->author_name);
        $authorUrl = Url::absolute($meta?->author_url, $this->publisher->baseUrl());
        $user = $discussion->firstPost?->user;

        if ($user === null && $discussion->relationLoaded('user')) {
            $user = $discussion->user;
        }

        if ($authorName === null && $user !== null) {
            $authorName = $this->cleanText($user->display_name ?: $user->username);
            $authorUrl = Url::join($this->publisher->baseUrl(), 'u/'.$user->username);
        }

        $authorName ??= $this->publisher->defaultAuthor();
        $language = Url::language($meta?->language, $this->publisher->defaultLanguage());

        $image = $meta?->cover_image;
        $imageUrl = $image !== null
            ? Url::absolute($image, $this->publisher->baseUrl())
            : $provider->coverImage($discussion);

        $categories = $provider->categories($discussion);
        $category = $this->cleanText($meta?->category)
            ?: ($categories[0]['name'] ?? null);
        $categoryUrl = $categories[0]['url'] ?? null;

        $publishedAt = $discussion->created_at instanceof Carbon
            ? $discussion->created_at->copy()
            : Carbon::parse($discussion->created_at);

        $modifiedAt = $publishedAt->copy();

        if ($discussion->firstPost?->edited_at) {
            $modifiedAt = $this->later($modifiedAt, $discussion->firstPost->edited_at);
        }

        if ($meta?->updated_at) {
            $modifiedAt = $this->later($modifiedAt, $meta->updated_at);
        }

        $isNews = (bool) ($meta?->is_news ?? false);

        return new NewsItem(
            discussionId: (int) $discussion->id,
            title: $title,
            url: $provider->canonicalUrl($discussion),
            publishedAt: $publishedAt,
            modifiedAt: $modifiedAt,
            language: $language,
            schemaType: $this->schemaType($isNews, $provider->isBlog()),
            provider: $provider->id(),
            isBlog: $provider->isBlog(),
            isNews: $isNews,
            structuredDataEnabled: (bool) ($meta?->enable_structured_data ?? true),
            description: $description,
            authorName: $authorName,
            authorUrl: $authorUrl,
            imageUrl: $imageUrl,
            category: $category ?: null,
            categoryUrl: $categoryUrl,
            blogUrl: $provider->blogOverviewUrl(),
            headline: $headline
        );
    }

    protected function schemaType(bool $isNews, bool $isBlog): string
    {
        if ($isNews) {
            return 'NewsArticle';
        }

        return $isBlog ? 'BlogPosting' : 'Article';
    }

    protected function later(Carbon $current, Carbon|\DateTimeInterface|string $candidate): Carbon
    {
        $date = $candidate instanceof Carbon
            ? $candidate
            : Carbon::parse($candidate);

        return $date->greaterThan($current) ? $date->copy() : $current;
    }

    protected function excerpt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = strip_tags($value);
        $text = preg_replace('/!\[[^\]]*]\([^)]*\)/u', ' ', $text) ?? '';
        $text = preg_replace('/\[([^\]]+)]\([^)]*\)/u', '$1', $text) ?? '';
        $text = preg_replace('/[#*_>`~]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if ($text === '') {
            return null;
        }

        return mb_strlen($text) > 300
            ? rtrim(mb_substr($text, 0, 297)).'...'
            : $text;
    }

    protected function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $value !== '' ? $value : null;
    }
}
