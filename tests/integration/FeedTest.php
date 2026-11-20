<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use PHPUnit\Framework\Attributes\Test;

class FeedTest extends GoogleNewsTestCase
{
    #[Test]
    public function rss_feed_contains_published_news(): void
    {
        $this->seedArticle([
            'title' => 'RSS news',
            'description' => 'RSS description',
            'headline' => 'RSS headline',
            'author_name' => 'Reporter',
            'cover_image' => 'https://example.com/image.jpg',
        ]);

        $response = $this->send($this->request('GET', '/news-feed.xml'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<title>RSS headline</title>', $body);
        $this->assertStringContainsString('<link>http://localhost/d/1-test-article</link>', $body);
        $this->assertStringContainsString('<dc:creator>Reporter</dc:creator>', $body);
        $this->assertStringContainsString('RSS description', $body);
        $this->assertStringContainsString('<media:content', $body);
        $this->assertNotFalse(simplexml_load_string($body));
    }

    #[Test]
    public function atom_feed_contains_published_news(): void
    {
        $this->seedArticle(['title' => 'Atom news', 'language' => 'tr']);

        $response = $this->send($this->request('GET', '/news-atom.xml'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<entry', $body);
        $this->assertStringContainsString('<title>Atom news</title>', $body);
        $this->assertStringContainsString('<published>', $body);
        $this->assertStringContainsString('<updated>', $body);
        $this->assertNotFalse(simplexml_load_string($body));
    }

    #[Test]
    public function feed_respects_include_in_feed_and_visibility(): void
    {
        $this->seedArticle(['title' => 'Included news']);
        $this->seedArticle(['title' => 'Excluded from feed', 'include_in_feed' => false]);
        $this->seedArticle(['title' => 'Hidden feed news', 'hidden' => true]);
        $this->seedArticle(['title' => 'Private feed news', 'private' => true]);

        $body = (string) $this->send($this->request('GET', '/news-feed.xml'))->getBody();

        $this->assertStringContainsString('Included news', $body);
        $this->assertStringNotContainsString('Excluded from feed', $body);
        $this->assertStringNotContainsString('Hidden feed news', $body);
        $this->assertStringNotContainsString('Private feed news', $body);
    }

    #[Test]
    public function feed_escapes_xml_and_keeps_turkish_characters(): void
    {
        $this->seedArticle([
            'title' => 'Türkçe <b>haber</b> & özel "başlık"',
            'author_name' => 'Yazar & Editör',
        ]);

        $body = (string) $this->send($this->request('GET', '/news-feed.xml'))->getBody();

        $this->assertStringContainsString('Türkçe haber &amp; özel &quot;başlık&quot;', $body);
        $this->assertStringContainsString('Yazar &amp; Editör', $body);
        $this->assertStringNotContainsString('<b>', $body);
        $this->assertNotFalse(simplexml_load_string($body));
    }

    #[Test]
    public function feed_limit_is_respected(): void
    {
        $this->setting('cengizkemec-google-news.feeds.limit', 1);

        $this->seedArticle(['title' => 'First feed item']);
        $this->seedArticle(['title' => 'Second feed item']);

        $body = (string) $this->send($this->request('GET', '/news-feed.xml'))->getBody();

        $this->assertEquals(1, substr_count($body, '<item>'));
    }
}
