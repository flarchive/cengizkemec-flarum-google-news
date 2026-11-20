<?php

namespace Cengizkemec\GoogleNews\Tests\unit;

use Carbon\Carbon;
use Cengizkemec\GoogleNews\Content\NewsItem;
use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\SitemapBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SitemapBuilderTest extends TestCase
{
    #[Test]
    public function news_sitemap_contains_required_elements(): void
    {
        $publisher = $this->publisher();
        $item = $this->item([
            'title' => 'A news headline',
            'url' => 'https://example.com/blog/1-news',
            'isNews' => true,
        ]);

        $result = (new SitemapBuilder())->news([$item], $publisher);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('<news:name>Example News</news:name>', $result['content']);
        $this->assertStringContainsString('<news:language>en</news:language>', $result['content']);
        $this->assertStringContainsString('<news:publication_date>', $result['content']);
        $this->assertStringContainsString('<news:title>A news headline</news:title>', $result['content']);
        $this->assertNotFalse(simplexml_load_string($result['content']));
    }

    #[Test]
    public function news_sitemap_excludes_non_news_and_old_items(): void
    {
        $publisher = $this->publisher();

        $regular = $this->item(['isNews' => false]);
        $old = $this->item(['publishedAt' => Carbon::now()->subHours(49), 'isNews' => true]);
        $fresh = $this->item(['publishedAt' => Carbon::now()->subHours(2), 'isNews' => true]);

        $result = (new SitemapBuilder())->news([$regular, $old, $fresh], $publisher);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('https://example.com/blog/1-news', $result['content']);
    }

    #[Test]
    public function news_sitemap_never_exceeds_one_thousand_entries(): void
    {
        $items = [];

        for ($i = 1; $i <= 1200; $i++) {
            $items[] = $this->item([
                'url' => 'https://example.com/blog/'.$i.'-news',
                'isNews' => true,
            ]);
        }

        $result = (new SitemapBuilder())->news($items, $this->publisher());

        $this->assertSame(1000, $result['count']);
        $this->assertSame(1000, substr_count($result['content'], '<url>'));
    }

    #[Test]
    public function content_sitemap_escapes_values_and_deduplicates_urls(): void
    {
        $item = $this->item([
            'title' => 'A & B <script>',
            'url' => 'https://example.com/blog/1?a=1&b=2',
        ]);

        $result = (new SitemapBuilder())->content([$item, $item]);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('https://example.com/blog/1?a=1&amp;b=2', $result['content']);
        $this->assertNotFalse(simplexml_load_string($result['content']));
    }

    protected function publisher(): PublisherConfig
    {
        $publisher = $this->createMock(PublisherConfig::class);
        $publisher->method('name')->willReturn('Example News');
        $publisher->method('website')->willReturn('https://example.com');
        $publisher->method('baseUrl')->willReturn('https://example.com');

        return $publisher;
    }

    protected function item(array $overrides = []): NewsItem
    {
        return new NewsItem(
            discussionId: 1,
            title: $overrides['title'] ?? 'News title',
            url: $overrides['url'] ?? 'https://example.com/blog/1-news',
            publishedAt: $overrides['publishedAt'] ?? Carbon::now()->subHour(),
            modifiedAt: $overrides['modifiedAt'] ?? Carbon::now(),
            language: $overrides['language'] ?? 'en',
            schemaType: ($overrides['isNews'] ?? true) ? 'NewsArticle' : 'BlogPosting',
            provider: 'test',
            isBlog: true,
            isNews: $overrides['isNews'] ?? true,
            description: 'Description',
            authorName: 'Author',
        );
    }
}
