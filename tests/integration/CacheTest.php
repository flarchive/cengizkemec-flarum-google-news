<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use Cengizkemec\GoogleNews\Models\NewsMeta;
use Cengizkemec\GoogleNews\Services\CacheService;
use PHPUnit\Framework\Attributes\Test;

class CacheTest extends GoogleNewsTestCase
{
    #[Test]
    public function sitemap_output_is_cached(): void
    {
        $this->seedArticle(['title' => 'Cached news']);

        $this->send($this->request('GET', '/news-sitemap.xml'));

        $cache = $this->app()->getContainer()->make(CacheService::class);
        $payload = $cache->payload(CacheService::NEWS_SITEMAP);

        $this->assertNotNull($payload);
        $this->assertEquals(1, $payload['count']);
        $this->assertStringContainsString('Cached news', $payload['content']);
    }

    #[Test]
    public function changing_news_metadata_invalidates_the_cache(): void
    {
        $this->seedArticle(['title' => 'Before update']);

        $this->send($this->request('GET', '/news-sitemap.xml'));

        $cache = $this->app()->getContainer()->make(CacheService::class);
        $this->assertNotNull($cache->payload(CacheService::NEWS_SITEMAP));

        $meta = NewsMeta::query()->firstOrFail();
        $meta->headline = 'After update';
        $meta->save();

        $this->assertNull($cache->payload(CacheService::NEWS_SITEMAP));
    }

    #[Test]
    public function cache_can_be_flushed(): void
    {
        $this->seedArticle();

        $this->send($this->request('GET', '/news-sitemap.xml'));

        $cache = $this->app()->getContainer()->make(CacheService::class);
        $this->assertNotNull($cache->payload(CacheService::NEWS_SITEMAP));

        $cache->flush();

        $this->assertNull($cache->payload(CacheService::NEWS_SITEMAP));
    }

    #[Test]
    public function status_endpoint_requires_an_administrator(): void
    {
        $this->seedArticle();

        $response = $this->send($this->requestAsUser($this->request('GET', '/api/google-news/status'), 2));

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function status_endpoint_reports_extension_state(): void
    {
        $this->seedArticle();

        $response = $this->send($this->requestAsUser($this->request('GET', '/api/google-news/status'), 1));
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($body['enabled']);
        $this->assertFalse($body['fof_blog']['enabled']);
        $this->assertTrue($body['features']['news_sitemap']);
        $this->assertArrayHasKey('news_sitemap', $body['endpoints']);
    }

    #[Test]
    public function deleting_content_invalidates_the_cache(): void
    {
        $this->seedArticle();

        $this->send($this->request('GET', '/news-sitemap.xml'));

        $cache = $this->app()->getContainer()->make(CacheService::class);
        $this->assertNotNull($cache->payload(CacheService::NEWS_SITEMAP));

        \Flarum\Discussion\Discussion::query()->findOrFail(1)->delete();

        $this->assertNull($cache->payload(CacheService::NEWS_SITEMAP));
    }

    #[Test]
    public function publisher_name_is_configurable(): void
    {
        $this->setting('cengizkemec-google-news.publisher_name', 'Custom Publisher');
        $this->seedArticle();

        $body = (string) $this->send($this->request('GET', '/news-sitemap.xml'))->getBody();

        $this->assertStringContainsString('<news:name>Custom Publisher</news:name>', $body);
    }
}
