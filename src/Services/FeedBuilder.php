<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Services;

use Carbon\Carbon;
use Cengizkemec\GoogleNews\Content\NewsItem;
use Cengizkemec\GoogleNews\Support\Xml;

/**
 * Standards-compliant RSS 2.0 and Atom 1.0 generation.
 */
class FeedBuilder
{
    /**
     * @param NewsItem[] $items
     * @return array{content: string, count: int}
     */
    public function rss(array $items, PublisherConfig $publisher, string $selfUrl): array
    {
        $items = array_values(array_filter($items, fn (NewsItem $item) => $item->isValid()));
        $lastBuild = $this->latestDate($items) ?? Carbon::now();

        $xml = Xml::declaration()
            .'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">'."\n"
            .'<channel>'."\n"
            .'<title>'.Xml::escape($publisher->name()).'</title>'."\n"
            .'<link>'.Xml::escape($publisher->website()).'</link>'."\n"
            .'<description>'.Xml::escape($publisher->feedDescription()).'</description>'."\n"
            .'<language>'.Xml::escape($publisher->defaultLanguage()).'</language>'."\n"
            .'<lastBuildDate>'.Xml::escape(Xml::rfc2822($lastBuild)).'</lastBuildDate>'."\n"
            .'<generator>Flarum Google News</generator>'."\n"
            .'<atom:link href="'.Xml::escape($selfUrl).'" rel="self" type="application/rss+xml"/>'."\n";

        foreach ($items as $item) {
            $xml .= $this->rssItem($item);
        }

        $xml .= "</channel>\n</rss>\n";

        return ['content' => $xml, 'count' => count($items)];
    }

    /**
     * @param NewsItem[] $items
     * @return array{content: string, count: int}
     */
    public function atom(array $items, PublisherConfig $publisher, string $selfUrl): array
    {
        $items = array_values(array_filter($items, fn (NewsItem $item) => $item->isValid()));
        $updated = $this->latestDate($items) ?? Carbon::now();

        $xml = Xml::declaration()
            .'<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="'.Xml::escape($publisher->defaultLanguage()).'">'."\n"
            .'<id>'.Xml::escape($selfUrl).'</id>'."\n"
            .'<title>'.Xml::escape($publisher->name()).'</title>'."\n"
            .'<subtitle>'.Xml::escape($publisher->feedDescription()).'</subtitle>'."\n"
            .'<updated>'.Xml::escape($updated->format('c')).'</updated>'."\n"
            .'<link rel="self" type="application/atom+xml" href="'.Xml::escape($selfUrl).'"/>'."\n"
            .'<link rel="alternate" type="text/html" href="'.Xml::escape($publisher->website()).'"/>'."\n"
            .'<author><name>'.Xml::escape($publisher->name()).'</name></author>'."\n"
            .'<generator uri="https://github.com/cengizkemec/flarum-google-news">Flarum Google News</generator>'."\n";

        foreach ($items as $item) {
            $xml .= $this->atomEntry($item);
        }

        $xml .= "</feed>\n";

        return ['content' => $xml, 'count' => count($items)];
    }

    protected function rssItem(NewsItem $item): string
    {
        $xml = '<item>'."\n"
            .'<title>'.Xml::escape($item->seoTitle()).'</title>'."\n"
            .'<link>'.Xml::escape($item->url).'</link>'."\n"
            .'<guid isPermaLink="true">'.Xml::escape($item->url).'</guid>'."\n"
            .'<pubDate>'.Xml::escape(Xml::rfc2822($item->publishedAt)).'</pubDate>'."\n";

        if ($item->description !== null && $item->description !== '') {
            $xml .= '<description>'.Xml::escape($item->description).'</description>'."\n";
        }

        if ($item->authorName !== null && $item->authorName !== '') {
            $xml .= '<dc:creator>'.Xml::escape($item->authorName).'</dc:creator>'."\n";
        }

        $xml .= '<dc:language>'.Xml::escape($item->language).'</dc:language>'."\n";

        if ($item->category !== null && $item->category !== '') {
            $xml .= '<category>'.Xml::escape($item->category).'</category>'."\n";
        }

        if ($item->imageUrl !== null) {
            $xml .= '<media:content url="'.Xml::escape($item->imageUrl).'" medium="image"'
                .($this->imageMime($item->imageUrl) ? ' type="'.Xml::escape($this->imageMime($item->imageUrl)).'"' : '')
                .'/>'."\n";
            $xml .= '<media:thumbnail url="'.Xml::escape($item->imageUrl).'"/>'."\n";
        }

        return $xml."</item>\n";
    }

    protected function atomEntry(NewsItem $item): string
    {
        $xml = '<entry xml:lang="'.Xml::escape($item->language).'">'."\n"
            .'<id>'.Xml::escape($item->url).'</id>'."\n"
            .'<title>'.Xml::escape($item->seoTitle()).'</title>'."\n"
            .'<link rel="alternate" type="text/html" href="'.Xml::escape($item->url).'"/>'."\n"
            .'<published>'.Xml::escape($item->publishedAt->format('c')).'</published>'."\n"
            .'<updated>'.Xml::escape($item->modifiedAt->format('c')).'</updated>'."\n";

        if ($item->authorName !== null && $item->authorName !== '') {
            $xml .= '<author><name>'.Xml::escape($item->authorName).'</name>';
            if ($item->authorUrl !== null) {
                $xml .= '<uri>'.Xml::escape($item->authorUrl).'</uri>';
            }
            $xml .= "</author>\n";
        }

        if ($item->description !== null && $item->description !== '') {
            $xml .= '<summary type="text">'.Xml::escape($item->description).'</summary>'."\n";
        }

        if ($item->category !== null && $item->category !== '') {
            $xml .= '<category term="'.Xml::escape($item->category).'"/>'."\n";
        }

        if ($item->imageUrl !== null) {
            $xml .= '<link rel="enclosure" href="'.Xml::escape($item->imageUrl).'"'
                .($this->imageMime($item->imageUrl) ? ' type="'.Xml::escape($this->imageMime($item->imageUrl)).'"' : '')
                .' length="0"/>'."\n";
        }

        return $xml."</entry>\n";
    }

    protected function imageMime(string $url): ?string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'avif' => 'image/avif',
            'svg' => 'image/svg+xml',
            default => null,
        };
    }

    /**
     * @param NewsItem[] $items
     */
    protected function latestDate(array $items): ?Carbon
    {
        $latest = null;

        foreach ($items as $item) {
            if ($latest === null || $item->modifiedAt->greaterThan($latest)) {
                $latest = $item->modifiedAt;
            }
        }

        return $latest;
    }
}
