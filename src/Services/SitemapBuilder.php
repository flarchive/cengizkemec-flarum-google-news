<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Services;

use Cengizkemec\GoogleNews\Content\NewsItem;
use Cengizkemec\GoogleNews\Support\Xml;

/**
 * Google News and general XML sitemap generation.
 *
 * The output is escaped with ENT_XML1; no dynamic value is ever concatenated
 * into markup without escaping.
 */
class SitemapBuilder
{
    private const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    private const NEWS_NS = 'http://www.google.com/schemas/sitemap-news/0.9';

    /**
     * @param NewsItem[] $items
     * @return array{content: string, count: int}
     */
    public function news(array $items, PublisherConfig $publisher, int $max = 1000): array
    {
        $urls = [];
        $count = 0;

        foreach ($items as $item) {
            if (! $item->isEligibleForNewsSitemap() || ! $item->isNews) {
                continue;
            }

            if (count($urls) >= $max) {
                break;
            }

            $urls[] = $this->newsUrl($item, $publisher);
            $count++;
        }

        $xml = Xml::declaration()
            .'<urlset xmlns="'.self::SITEMAP_NS.'" xmlns:news="'.self::NEWS_NS.'">'."\n"
            .implode("\n", $urls)
            ."\n</urlset>\n";

        return ['content' => $xml, 'count' => $count];
    }

    /**
     * @param NewsItem[] $items
     * @return array{content: string, count: int}
     */
    public function content(array $items, int $max = 5000): array
    {
        $urls = [];
        $seen = [];

        foreach ($items as $item) {
            if (! $item->isValid() || isset($seen[$item->url])) {
                continue;
            }

            if (count($urls) >= $max) {
                break;
            }

            $seen[$item->url] = true;

            $urls[] = '<url>'
                .'<loc>'.Xml::escape($item->url).'</loc>'
                .'<lastmod>'.Xml::escape($item->modifiedAt->format('c')).'</lastmod>'
                .'</url>';
        }

        $xml = Xml::declaration()
            .'<urlset xmlns="'.self::SITEMAP_NS.'">'."\n"
            .implode("\n", $urls)
            ."\n</urlset>\n";

        return ['content' => $xml, 'count' => count($urls)];
    }

    protected function newsUrl(NewsItem $item, PublisherConfig $publisher): string
    {
        return '<url>'
            .'<loc>'.Xml::escape($item->url).'</loc>'
            .'<news:news>'
            .'<news:publication>'
            .'<news:name>'.Xml::escape($publisher->name()).'</news:name>'
            .'<news:language>'.Xml::escape($item->language).'</news:language>'
            .'</news:publication>'
            .'<news:publication_date>'.Xml::escape($item->publishedAt->format('c')).'</news:publication_date>'
            .'<news:title>'.Xml::escape($item->seoTitle()).'</news:title>'
            .'</news:news>'
            .'<lastmod>'.Xml::escape($item->modifiedAt->format('c')).'</lastmod>'
            .'</url>';
    }
}
