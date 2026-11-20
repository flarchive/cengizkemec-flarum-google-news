<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Integrations;

use Flarum\Discussion\Discussion;

/**
 * A content provider maps a Flarum discussion to a public article URL and
 * supplies provider-specific metadata. New providers can be registered in the
 * ProviderRegistry without changing sitemap, feed or JSON-LD code.
 */
interface ContentProviderInterface
{
    public function id(): string;

    public function isAvailable(): bool;

    public function supports(Discussion $discussion): bool;

    public function isBlog(): bool;

    /**
     * @return string Absolute canonical article URL.
     */
    public function canonicalUrl(Discussion $discussion): string;

    public function summary(Discussion $discussion): ?string;

    public function coverImage(Discussion $discussion): ?string;

    /**
     * @return array<int, array{name: string, url: string|null}>
     */
    public function categories(Discussion $discussion): array;

    public function blogOverviewUrl(): ?string;
}
