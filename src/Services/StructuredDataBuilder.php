<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Services;

use Cengizkemec\GoogleNews\Content\NewsItem;
use Cengizkemec\GoogleNews\Support\Url;

/**
 * Builds Schema.org JSON-LD for NewsArticle, BlogPosting, Article,
 * Organization, WebSite and BreadcrumbList.
 */
class StructuredDataBuilder
{
    /**
     * @param array<int, array{name: string, url: string}> $breadcrumbs
     */
    public function build(
        NewsItem $item,
        PublisherConfig $publisher,
        Settings $settings,
        array $breadcrumbs = []
    ): ?string {
        $graph = [];
        $base = rtrim($publisher->baseUrl(), '/');
        $organizationId = $base.'/#organization';
        $websiteId = $base.'/#website';

        $type = $this->type($item, $settings);

        if ($type !== null && $item->structuredDataEnabled) {
            $article = [
                '@type' => $type,
                '@id' => $item->url.'#article',
                'headline' => mb_substr($item->seoTitle(), 0, 110),
                'name' => $item->title,
                'url' => $item->url,
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $item->url,
                ],
                'datePublished' => $item->publishedAt->format('c'),
                'dateModified' => $item->modifiedAt->format('c'),
                'inLanguage' => $item->language,
                'isAccessibleForFree' => true,
            ];

            if ($item->description !== null && $item->description !== '') {
                $article['description'] = $item->description;
            }

            if ($item->imageUrl !== null) {
                $article['image'] = [
                    [
                        '@type' => 'ImageObject',
                        'url' => $item->imageUrl,
                    ],
                ];
            }

            if ($item->authorName !== null && $item->authorName !== '') {
                $author = [
                    '@type' => 'Person',
                    'name' => $item->authorName,
                ];

                if ($item->authorUrl !== null) {
                    $author['url'] = $item->authorUrl;
                }

                $article['author'] = $author;
            }

            $article['publisher'] = $this->publisherNode($publisher, $organizationId);
            $article['isPartOf'] = ['@id' => $websiteId];

            if ($item->category !== null && $item->category !== '') {
                $article['articleSection'] = $item->category;
            }

            $graph[] = $article;
        }

        if ($settings->schemaEnabled('organization')) {
            $organization = $this->publisherNode($publisher, $organizationId);
            $organization['@id'] = $organizationId;

            if ($publisher->country() !== null) {
                $organization['address'] = [
                    '@type' => 'PostalAddress',
                    'addressCountry' => $publisher->country(),
                ];
            }

            $graph[] = $organization;
        }

        if ($settings->schemaEnabled('website')) {
            $graph[] = [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'url' => $base.'/',
                'name' => $publisher->name(),
                'inLanguage' => $item->language,
                'publisher' => ['@id' => $organizationId],
            ];
        }

        if ($settings->schemaEnabled('breadcrumb') && $breadcrumbs !== []) {
            $elements = [];

            foreach (array_values($breadcrumbs) as $position => $crumb) {
                if (empty($crumb['name']) || empty($crumb['url'])) {
                    continue;
                }

                $elements[] = [
                    '@type' => 'ListItem',
                    'position' => count($elements) + 1,
                    'name' => $crumb['name'],
                    'item' => Url::absolute($crumb['url'], $base),
                ];
            }

            if (count($elements) >= 2) {
                $graph[] = [
                    '@type' => 'BreadcrumbList',
                    '@id' => $item->url.'#breadcrumb',
                    'itemListElement' => $elements,
                ];
            }
        }

        if ($graph === []) {
            return null;
        }

        $json = json_encode(
            [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ],
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        if ($json === false) {
            return null;
        }

        return '<script type="application/ld+json">'.$json.'</script>';
    }

    protected function type(NewsItem $item, Settings $settings): ?string
    {
        $type = $item->schemaType;

        if ($type === 'NewsArticle') {
            if ($settings->schemaEnabled('news_article')) {
                return $type;
            }

            $type = $item->isBlog ? 'BlogPosting' : 'Article';
        }

        if ($type === 'BlogPosting') {
            if ($settings->schemaEnabled('blog_posting')) {
                return $type;
            }

            $type = 'Article';
        }

        return $settings->schemaEnabled('article') ? $type : null;
    }

    protected function publisherNode(PublisherConfig $publisher, string $organizationId): array
    {
        $node = [
            '@type' => 'Organization',
            '@id' => $organizationId,
            'name' => $publisher->name(),
            'url' => $publisher->website(),
        ];

        if ($publisher->logoUrl() !== null) {
            $logo = [
                '@type' => 'ImageObject',
                'url' => $publisher->logoUrl(),
            ];

            if ($publisher->logoMimeType() !== null) {
                $logo['encodingFormat'] = $publisher->logoMimeType();
            }

            $node['logo'] = $logo;
        }

        return $node;
    }
}
