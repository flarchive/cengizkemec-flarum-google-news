<?php

namespace Cengizkemec\GoogleNews\Tests\unit;

use Carbon\Carbon;
use Cengizkemec\GoogleNews\Content\NewsItem;
use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\Settings;
use Cengizkemec\GoogleNews\Services\StructuredDataBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StructuredDataBuilderTest extends TestCase
{
    #[Test]
    public function news_article_json_ld_has_required_properties(): void
    {
        $json = $this->decode((new StructuredDataBuilder())->build(
            $this->item(),
            $this->publisher(),
            $this->settings(),
            [
                ['name' => 'Home', 'url' => 'https://example.com/'],
                ['name' => 'Blog', 'url' => 'https://example.com/blog'],
                ['name' => 'Türkçe başlık', 'url' => 'https://example.com/blog/1-news'],
            ]
        ));

        $article = $this->findType($json, 'NewsArticle');

        $this->assertNotNull($article);
        $this->assertSame('Türkçe başlık', $article['headline']);
        $this->assertSame('https://example.com/blog/1-news', $article['mainEntityOfPage']['@id']);
        $this->assertSame('tr', $article['inLanguage']);
        $this->assertSame('2026-11-20T10:00:00+00:00', $article['datePublished']);
        $this->assertSame('2026-11-20T11:00:00+00:00', $article['dateModified']);
        $this->assertSame('Example News', $article['publisher']['name']);
        $this->assertSame('Author', $article['author']['name']);
        $this->assertSame('https://example.com/cover.jpg', $article['image'][0]['url']);
        $this->assertNotNull($this->findType($json, 'Organization'));
        $this->assertNotNull($this->findType($json, 'WebSite'));
        $this->assertNotNull($this->findType($json, 'BreadcrumbList'));
    }

    #[Test]
    public function regular_blog_posts_use_blog_posting(): void
    {
        $json = $this->decode((new StructuredDataBuilder())->build(
            $this->item(['isNews' => false, 'schemaType' => 'BlogPosting']),
            $this->publisher(),
            $this->settings()
        ));

        $this->assertNotNull($this->findType($json, 'BlogPosting'));
        $this->assertNull($this->findType($json, 'NewsArticle'));
    }

    #[Test]
    public function disabled_article_type_is_not_emitted(): void
    {
        $settings = $this->settings(['news_article' => false, 'blog_posting' => false, 'article' => false]);

        $json = $this->decode((new StructuredDataBuilder())->build($this->item(), $this->publisher(), $settings));

        $this->assertNull($this->findType($json, 'NewsArticle'));
        $this->assertNull($this->findType($json, 'Article'));
        $this->assertNotNull($this->findType($json, 'Organization'));
    }

    #[Test]
    public function json_ld_cannot_break_out_of_the_script_tag(): void
    {
        $item = $this->item(['headline' => '</script><script>alert(1)</script>']);

        $html = (new StructuredDataBuilder())->build($item, $this->publisher(), $this->settings());

        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringContainsString('\\u003C/script\\u003E', $html);
        $this->assertNotFalse(json_decode(strip_tags($html), true));
    }

    protected function decode(?string $html): array
    {
        $this->assertNotNull($html);
        preg_match('#<script type="application/ld\+json">(.*)</script>#s', $html, $matches);

        $this->assertNotEmpty($matches[1]);

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    protected function findType(array $json, string $type): ?array
    {
        foreach ($json['@graph'] as $node) {
            if (($node['@type'] ?? null) === $type) {
                return $node;
            }
        }

        return null;
    }

    protected function settings(array $overrides = []): Settings
    {
        $settings = $this->createMock(Settings::class);
        $settings->method('schemaEnabled')->willReturnCallback(
            fn (string $type) => $overrides[$type] ?? true
        );

        return $settings;
    }

    protected function publisher(): PublisherConfig
    {
        $publisher = $this->createMock(PublisherConfig::class);
        $publisher->method('name')->willReturn('Example News');
        $publisher->method('website')->willReturn('https://example.com');
        $publisher->method('baseUrl')->willReturn('https://example.com');
        $publisher->method('logoUrl')->willReturn('https://example.com/logo.png');
        $publisher->method('logoMimeType')->willReturn('image/png');
        $publisher->method('country')->willReturn('TR');

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
            schemaType: $overrides['schemaType'] ?? 'NewsArticle',
            provider: 'test',
            isBlog: true,
            isNews: $overrides['isNews'] ?? true,
            description: 'Açıklama',
            authorName: 'Author',
            imageUrl: 'https://example.com/cover.jpg',
            headline: $overrides['headline'] ?? 'Türkçe başlık',
        );
    }
}
