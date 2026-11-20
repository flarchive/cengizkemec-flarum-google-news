<?php

namespace Cengizkemec\GoogleNews\Tests\unit;

use Carbon\Carbon;
use Cengizkemec\GoogleNews\Content\NewsItem;
use Cengizkemec\GoogleNews\Services\FeedBuilder;
use Cengizkemec\GoogleNews\Services\PublisherConfig;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FeedBuilderTest extends TestCase
{
    #[Test]
    public function rss_feed_is_standards_compliant(): void
    {
        $result = (new FeedBuilder())->rss([$this->item()], $this->publisher(), 'https://example.com/news-feed.xml');

        $xml = simplexml_load_string($result['content']);
        $this->assertNotFalse($xml);
        $this->assertSame('2.0', (string) $xml['version']);
        $this->assertSame('Example News', (string) $xml->channel->title);
        $this->assertSame('Türkçe başlık', (string) $xml->channel->item->title);
        $this->assertSame('https://example.com/blog/1-news', (string) $xml->channel->item->link);
        $this->assertSame('Author', (string) $xml->channel->item->children('dc', true)->creator);
        $this->assertSame(1, $result['count']);
    }

    #[Test]
    public function atom_feed_is_standards_compliant(): void
    {
        $result = (new FeedBuilder())->atom([$this->item()], $this->publisher(), 'https://example.com/news-atom.xml');

        $xml = simplexml_load_string($result['content']);
        $this->assertNotFalse($xml);
        $this->assertSame('https://example.com/news-atom.xml', (string) $xml->id);
        $this->assertSame('Türkçe başlık', (string) $xml->entry->title);
        $this->assertSame('https://example.com/blog/1-news', (string) $xml->entry->id);
        $this->assertSame('tr', (string) $xml->attributes('xml', true)->lang);
        $this->assertSame(1, $result['count']);
    }

    #[Test]
    public function feed_escapes_xml_characters(): void
    {
        $item = $this->item([
            'title' => 'Türkçe <script>alert("x")</script> & "quotes"',
            'authorName' => '<b>Author</b> & Co',
        ]);

        $result = (new FeedBuilder())->rss([$item], $this->publisher(), 'https://example.com/feed.xml');

        $this->assertStringNotContainsString('<script>', $result['content']);
        $this->assertStringContainsString('&lt;script&gt;', $result['content']);
        $this->assertNotFalse(simplexml_load_string($result['content']));
    }

    protected function publisher(): PublisherConfig
    {
        $publisher = $this->createMock(PublisherConfig::class);
        $publisher->method('name')->willReturn('Example News');
        $publisher->method('website')->willReturn('https://example.com');
        $publisher->method('baseUrl')->willReturn('https://example.com');
        $publisher->method('feedDescription')->willReturn('Latest news');
        $publisher->method('defaultLanguage')->willReturn('tr');

        return $publisher;
    }

    protected function item(array $overrides = []): NewsItem
    {
        return new NewsItem(
            discussionId: 1,
            title: $overrides['title'] ?? 'Türkçe başlık',
            url: 'https://example.com/blog/1-news',
            publishedAt: Carbon::parse('2026-11-20 10:00:00'),
            modifiedAt: Carbon::parse('2026-11-20 11:00:00'),
            language: 'tr',
            schemaType: 'NewsArticle',
            provider: 'test',
            isBlog: true,
            isNews: true,
            description: 'Açıklama',
            authorName: $overrides['authorName'] ?? 'Author',
            imageUrl: 'https://example.com/image.jpg',
        );
    }
}
