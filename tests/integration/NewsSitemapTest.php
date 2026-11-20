<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class NewsSitemapTest extends GoogleNewsTestCase
{
    #[Test]
    public function recent_news_articles_are_included(): void
    {
        $this->seedArticle(['title' => 'Fresh news']);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringContainsString('<loc>http://localhost/d/1-test-article</loc>', $body);
        $this->assertStringContainsString('<news:title>Fresh news</news:title>', $body);
        $this->assertStringContainsString('<news:language>en</news:language>', $body);
    }

    #[Test]
    public function articles_older_than_48_hours_are_excluded(): void
    {
        $this->seedArticle(['title' => 'Old news', 'created_at' => Carbon::now()->subHours(49)]);
        $this->seedArticle(['title' => 'Fresh news', 'created_at' => Carbon::now()->subHours(47)]);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringNotContainsString('Old news', $body);
        $this->assertStringContainsString('Fresh news', $body);
    }

    #[Test]
    public function hidden_private_and_pending_articles_are_excluded(): void
    {
        $this->withBlog();

        $this->seedArticle(['title' => 'Hidden news', 'hidden' => true]);
        $this->seedArticle(['title' => 'Private news', 'private' => true]);
        $this->seedArticle(['title' => 'Hidden post news', 'post_hidden' => true]);
        $this->seedArticle(['title' => 'Pending blog news', 'blog' => true, 'pending' => true]);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringNotContainsString('Hidden news', $body);
        $this->assertStringNotContainsString('Private news', $body);
        $this->assertStringNotContainsString('Hidden post news', $body);
        $this->assertStringNotContainsString('Pending blog news', $body);
    }

    #[Test]
    public function sitemap_flag_and_news_flag_are_respected(): void
    {
        $this->seedArticle(['title' => 'Not in sitemap', 'include_in_sitemap' => false]);
        $this->seedArticle(['title' => 'Not news', 'is_news' => false]);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringNotContainsString('Not in sitemap', $body);
        $this->assertStringNotContainsString('Not news', $body);
    }

    #[Test]
    public function xml_injection_is_escaped(): void
    {
        $this->seedArticle(['title' => 'News <script>alert("x")</script> & "quotes"']);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringContainsString('News alert(&quot;x&quot;) &amp; &quot;quotes&quot;', $body);
        $this->assertNotFalse(simplexml_load_string($body));
    }

    #[Test]
    public function turkish_characters_are_preserved(): void
    {
        $this->seedArticle(['title' => 'Türkçe “haber” başlığı — ğüşiöç']);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringContainsString('Türkçe “haber” başlığı — ğüşiöç', $body);
        $this->assertNotFalse(simplexml_load_string($body));
    }

    #[Test]
    public function custom_headline_and_language_are_used(): void
    {
        $this->seedArticle([
            'title' => 'Original title',
            'headline' => 'Custom headline',
            'language' => 'tr',
        ]);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringContainsString('<news:title>Custom headline</news:title>', $body);
        $this->assertStringContainsString('<news:language>tr</news:language>', $body);
    }

    #[Test]
    public function general_sitemap_keeps_older_content(): void
    {
        $this->seedArticle(['title' => 'Old news', 'created_at' => Carbon::now()->subDays(10)]);

        $body = (string) $this->send($this->request('GET', '/google-news-sitemap.xml'))->getBody();

        $this->assertStringContainsString('<loc>http://localhost/d/1-test-article</loc>', $body);
        $this->assertStringContainsString('<lastmod>', $body);
    }

    #[Test]
    public function fof_blog_articles_use_the_blog_canonical_url(): void
    {
        $this->withBlog();
        $this->seedArticle(['title' => 'Blog news', 'blog' => true]);

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringContainsString('<loc>http://localhost/blog/1-test-article</loc>', $body);
        $this->assertStringNotContainsString('<loc>http://localhost/d/1-test-article</loc>', $body);
    }

    #[Test]
    public function deleted_content_disappears_after_cache_regeneration(): void
    {
        $this->seedArticle(['title' => 'To be deleted']);

        $first = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();
        $this->assertStringContainsString('To be deleted', $first);

        \Flarum\Discussion\Discussion::query()->findOrFail(1)->delete();

        $second = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();
        $this->assertStringNotContainsString('To be deleted', $second);
        $this->assertNotFalse(simplexml_load_string($second));
    }
}
