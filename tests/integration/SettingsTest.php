<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use PHPUnit\Framework\Attributes\Test;

class SettingsTest extends GoogleNewsTestCase
{
    #[Test]
    public function disabling_the_extension_returns_not_found(): void
    {
        $this->setting('cengizkemec-google-news.enabled', false);
        $this->seedArticle();

        $this->assertEquals(404, $this->send($this->request('GET', '/news-sitemap.xml'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/news-feed.xml'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/news-atom.xml'))->getStatusCode());
    }

    #[Test]
    public function individual_endpoints_can_be_disabled(): void
    {
        $this->setting('cengizkemec-google-news.sitemap.enabled', false);
        $this->setting('cengizkemec-google-news.feeds.rss_enabled', false);
        $this->seedArticle();

        $this->assertEquals(404, $this->send($this->request('GET', '/news-sitemap.xml'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/news-feed.xml'))->getStatusCode());
        $this->assertEquals(200, $this->send($this->request('GET', '/news-atom.xml'))->getStatusCode());
    }

    #[Test]
    public function empty_checkbox_values_saved_by_the_admin_panel_disable_endpoints(): void
    {
        // Flarum saves an unchecked checkbox as an empty string.
        $this->setting('cengizkemec-google-news.feeds.atom_enabled', '');
        $this->setting('cengizkemec-google-news.sitemap.enabled', '');
        $this->seedArticle();

        $this->assertEquals(404, $this->send($this->request('GET', '/news-atom.xml'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/news-sitemap.xml'))->getStatusCode());
        $this->assertEquals(200, $this->send($this->request('GET', '/news-feed.xml'))->getStatusCode());
    }

    #[Test]
    public function robots_metadata_is_configurable(): void
    {
        $this->withBlog();
        $this->setting('cengizkemec-google-news.seo.robots', 'noindex,nofollow');
        $this->seedArticle(['blog' => true, 'is_news' => true]);

        $body = (string) $this->send($this->request('GET', '/blog/1-test-article'))->getBody();

        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow">', $body);
    }

    #[Test]
    public function canonical_metadata_can_be_disabled(): void
    {
        $this->withBlog();
        $this->setting('cengizkemec-google-news.seo.canonical', false);
        $this->seedArticle(['blog' => true, 'is_news' => true]);

        $body = (string) $this->send($this->request('GET', '/blog/1-test-article'))->getBody();

        $this->assertStringNotContainsString('rel="canonical"', $body);
    }

    #[Test]
    public function open_graph_and_twitter_can_be_disabled(): void
    {
        $this->withBlog();
        $this->setting('cengizkemec-google-news.seo.open_graph', false);
        $this->setting('cengizkemec-google-news.seo.twitter', false);
        $this->seedArticle(['blog' => true, 'is_news' => true]);

        $body = (string) $this->send($this->request('GET', '/blog/1-test-article'))->getBody();

        $this->assertStringNotContainsString('property="og:', $body);
        $this->assertStringNotContainsString('name="twitter:', $body);
    }
}
