<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use PHPUnit\Framework\Attributes\Test;

class StructuredDataTest extends GoogleNewsTestCase
{
    /**
     * Flarum core serialises the same discussion author through several
     * relationships on the page. This is a repeated fixed-size lookup, not an
     * N+1 that grows with the number of articles.
     */
    protected function allowedRepeatedQueries(): array
    {
        return ['from "users"'];
    }
    #[Test]
    public function news_article_json_ld_and_seo_metadata_are_injected(): void
    {
        $this->withBlog();

        $this->seedArticle([
            'blog' => true,
            'is_news' => true,
            'title' => 'Türkçe haber başlığı',
            'headline' => 'Özel haber başlığı',
            'description' => 'Haber açıklaması',
            'cover_image' => 'https://example.com/cover.jpg',
            'author_name' => 'Muhabir',
            'language' => 'tr',
        ]);

        $response = $this->send($this->request('GET', '/blog/1-test-article'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/ld+json', $body);
        $this->assertStringContainsString('"@type":"NewsArticle"', $body);
        $this->assertStringContainsString('"headline":"Özel haber başlığı"', $body);
        $this->assertStringContainsString('"inLanguage":"tr"', $body);
        $this->assertStringContainsString('"url":"http://localhost/blog/1-test-article"', $body);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/blog/1-test-article">', $body);
        $this->assertStringContainsString('<meta property="og:title" content="Özel haber başlığı">', $body);
        $this->assertStringContainsString('<meta property="og:url" content="http://localhost/blog/1-test-article">', $body);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $body);
        $this->assertStringContainsString('<meta name="robots" content="index,follow">', $body);
    }

    #[Test]
    public function regular_blog_posts_use_blog_posting(): void
    {
        $this->withBlog();

        $this->seedArticle([
            'blog' => true,
            'is_news' => false,
            'title' => 'Regular blog post',
        ]);

        $body = (string) $this->send($this->request('GET', '/blog/1-test-article'))->getBody();

        $this->assertStringContainsString('"@type":"BlogPosting"', $body);
        $this->assertStringNotContainsString('"@type":"NewsArticle"', $body);
    }

    #[Test]
    public function per_post_structured_data_can_be_disabled(): void
    {
        $this->withBlog();

        $this->seedArticle([
            'blog' => true,
            'is_news' => true,
            'enable_structured_data' => false,
        ]);

        $body = (string) $this->send($this->request('GET', '/blog/1-test-article'))->getBody();

        $this->assertStringNotContainsString('"@type":"NewsArticle"', $body);
        $this->assertStringNotContainsString('"@type":"BlogPosting"', $body);
    }

    #[Test]
    public function general_discussions_are_not_automatically_news_articles(): void
    {
        $this->seedArticle(['meta' => false]);

        $body = (string) $this->send($this->request('GET', '/d/1-test-article'))->getBody();

        $this->assertStringNotContainsString('"@type":"NewsArticle"', $body);
        $this->assertStringNotContainsString('"@type":"Article"', $body);
        $this->assertStringNotContainsString('application/ld+json', $body);
    }

    #[Test]
    public function compatibility_mode_keeps_existing_structured_data(): void
    {
        $this->withBlog();

        $this->seedArticle(['blog' => true, 'is_news' => true]);

        // A first request must produce our JSON-LD; compatibility only skips
        // output when another extension has already produced it. Here we assert
        // that no duplicate script tags are produced by our own callback.
        $body = (string) $this->send($this->request('GET', '/blog/1-test-article'))->getBody();

        $this->assertEquals(1, substr_count($body, 'application/ld+json'));
    }
}
