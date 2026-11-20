<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Content;

use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\Settings;
use Cengizkemec\GoogleNews\Services\StructuredDataBuilder;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Adds server-rendered SEO metadata and JSON-LD to public article pages.
 *
 * It runs after core and other SEO extensions (priority -100), so it can
 * detect existing Open Graph / JSON-LD output and avoid duplicates.
 */
class StructuredDataInjector
{
    public function __construct(
        protected NewsRepository $repository,
        protected StructuredDataBuilder $builder,
        protected PublisherConfig $publisher,
        protected Settings $settings,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator,
        protected ExtensionManager $extensions
    ) {
    }

    public function __invoke(Document $document, ServerRequestInterface $request): void
    {
        if (! $this->settings->isEnabled()) {
            return;
        }

        $routeName = $request->getAttribute('routeName');

        if (! in_array($routeName, ['blog.post', 'discussion'], true)) {
            return;
        }

        $apiDocument = $document->payload['apiDocument'] ?? null;
        $id = is_object($apiDocument)
            ? ($apiDocument->data->id ?? null)
            : ($apiDocument['data']['id'] ?? null);

        if ($id === null) {
            return;
        }

        $discussion = Discussion::query()
            ->whereVisibleTo(new \Flarum\User\Guest())
            ->find((int) $id);

        if (! $discussion instanceof Discussion) {
            return;
        }

        $item = $this->repository->forDiscussion($discussion);

        if ($item === null) {
            return;
        }

        $this->applyDocumentMetadata($document, $item, $routeName);
        $this->applySocialMetadata($document, $item);
        $this->applyStructuredData($document, $item);
        $this->applyFeedLinks($document);
    }

    protected function applyDocumentMetadata(Document $document, NewsItem $item, ?string $routeName): void
    {
        $respectExisting = $this->respectsExistingSeo();

        if (! $respectExisting && ($routeName === 'blog.post' || $item->headline !== null)) {
            $document->title = $item->seoTitle();
        }

        if ($this->settings->seoEnabled('canonical') && (! $respectExisting || $document->canonicalUrl === null)) {
            $document->canonicalUrl = $item->url;
        }

        if ($this->settings->seoEnabled('description') && $item->description !== null && ! $this->hasMeta($document, 'name="description"')) {
            if (! $respectExisting || ! isset($document->meta['description'])) {
                $document->meta['description'] = $item->description;
            }
        }

        if (! isset($document->meta['robots']) && ! $this->hasMeta($document, 'name="robots"')) {
            $document->meta['robots'] = $this->settings->robots();
        }
    }

    protected function respectsExistingSeo(): bool
    {
        return $this->settings->compatibilityMode()
            && $this->extensions->isEnabled('fof-seo');
    }

    protected function applySocialMetadata(Document $document, NewsItem $item): void
    {
        if (! $this->settings->seoEnabled('open_graph') && ! $this->settings->seoEnabled('twitter')) {
            return;
        }

        if ($this->settings->compatibilityMode() && $this->hasOpenGraph($document)) {
            return;
        }

        $title = $this->escape($item->seoTitle());
        $description = $this->escape($item->description ?? '');
        $url = $this->escape($item->url);
        $siteName = $this->escape($this->publisher->name());
        $imageUrl = $item->imageUrl ?? $this->publisher->logoUrl();
        $image = $imageUrl !== null ? $this->escape($imageUrl) : null;

        if ($this->settings->seoEnabled('open_graph')) {
            $document->head[] = '<meta property="og:type" content="'.$this->escape($this->settings->openGraphType()).'">';
            $document->head[] = '<meta property="og:title" content="'.$title.'">';
            $document->head[] = '<meta property="og:url" content="'.$url.'">';
            $document->head[] = '<meta property="og:site_name" content="'.$siteName.'">';

            if ($description !== '') {
                $document->head[] = '<meta property="og:description" content="'.$description.'">';
            }

            if ($image !== null) {
                $document->head[] = '<meta property="og:image" content="'.$image.'">';
            }

            $document->head[] = '<meta property="article:published_time" content="'.$this->escape($item->publishedAt->format('c')).'">';
            $document->head[] = '<meta property="article:modified_time" content="'.$this->escape($item->modifiedAt->format('c')).'">';

            if ($item->authorName !== null) {
                $document->head[] = '<meta property="article:author" content="'.$this->escape($item->authorName).'">';
            }

            if ($item->category !== null) {
                $document->head[] = '<meta property="article:section" content="'.$this->escape($item->category).'">';
            }
        }

        if ($this->settings->seoEnabled('twitter')) {
            $document->head[] = '<meta name="twitter:card" content="'.($image !== null ? 'summary_large_image' : 'summary').'">';
            $document->head[] = '<meta name="twitter:title" content="'.$title.'">';

            if ($description !== '') {
                $document->head[] = '<meta name="twitter:description" content="'.$description.'">';
            }

            if ($image !== null) {
                $document->head[] = '<meta name="twitter:image" content="'.$image.'">';
            }
        }
    }

    protected function applyStructuredData(Document $document, NewsItem $item): void
    {
        if ($this->settings->compatibilityMode() && $this->hasStructuredData($document)) {
            return;
        }

        $breadcrumbs = [
            [
                'name' => $this->translator->trans('cengizkemec-google-news.lib.home'),
                'url' => rtrim($this->publisher->baseUrl(), '/').'/',
            ],
        ];

        if ($item->blogUrl !== null) {
            $breadcrumbs[] = [
                'name' => $this->translator->trans('cengizkemec-google-news.lib.blog'),
                'url' => $item->blogUrl,
            ];
        }

        if ($item->category !== null && $item->categoryUrl !== null) {
            $breadcrumbs[] = [
                'name' => $item->category,
                'url' => $item->categoryUrl,
            ];
        }

        $breadcrumbs[] = [
            'name' => $item->title,
            'url' => $item->url,
        ];

        $schema = $this->builder->build($item, $this->publisher, $this->settings, $breadcrumbs);

        if ($schema !== null) {
            $document->head[] = $schema;
        }
    }

    protected function applyFeedLinks(Document $document): void
    {
        $base = rtrim($this->publisher->baseUrl(), '/');
        $title = $this->escape($this->publisher->name());

        if ($this->settings->bool('feeds.rss_enabled', true)) {
            $document->head[] = '<link rel="alternate" type="application/rss+xml" title="'.$title.' RSS" href="'
                .$this->escape($base.'/news-feed.xml').'">';
        }

        if ($this->settings->bool('feeds.atom_enabled', true)) {
            $document->head[] = '<link rel="alternate" type="application/atom+xml" title="'.$title.' Atom" href="'
                .$this->escape($base.'/news-atom.xml').'">';
        }
    }

    protected function hasStructuredData(Document $document): bool
    {
        foreach ($document->head as $tag) {
            if (str_contains($tag, 'application/ld+json')) {
                return true;
            }
        }

        return false;
    }

    protected function hasOpenGraph(Document $document): bool
    {
        foreach ($document->head as $tag) {
            if (str_contains($tag, 'property="og:') || str_contains($tag, 'name="twitter:')) {
                return true;
            }
        }

        return false;
    }

    protected function hasMeta(Document $document, string $needle): bool
    {
        foreach ($document->head as $tag) {
            if (str_contains($tag, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
