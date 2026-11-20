<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExtensionBootTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('cengizkemec-google-news');
    }

    #[Test]
    public function extension_boots_without_fof_blog(): void
    {
        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(
            $this->app()->getContainer()->make(\Illuminate\Database\Schema\Builder::class)->hasTable('google_news_meta')
        );
    }

    #[Test]
    public function controller_dependencies_resolve(): void
    {
        $container = $this->app()->getContainer();

        foreach ([
            \Cengizkemec\GoogleNews\Api\Controller\NewsSitemapController::class,
            \Cengizkemec\GoogleNews\Api\Controller\ContentSitemapController::class,
            \Cengizkemec\GoogleNews\Api\Controller\RssFeedController::class,
            \Cengizkemec\GoogleNews\Api\Controller\AtomFeedController::class,
            \Cengizkemec\GoogleNews\Api\Controller\StatusController::class,
            \Cengizkemec\GoogleNews\Api\Controller\ClearCacheController::class,
            \Cengizkemec\GoogleNews\Api\Controller\UploadPublisherLogoController::class,
            \Cengizkemec\GoogleNews\Api\Controller\DeletePublisherLogoController::class,
        ] as $controller) {
            $this->assertInstanceOf($controller, $container->make($controller));
        }
    }

    #[Test]
    public function news_sitemap_is_served(): void
    {
        $response = $this->send($this->request('GET', '/news-sitemap.xml'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/xml', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<urlset', (string) $response->getBody());
    }

    #[Test]
    public function rss_and_atom_feeds_are_served(): void
    {
        $rss = $this->send($this->request('GET', '/news-feed.xml'));
        $atom = $this->send($this->request('GET', '/news-atom.xml'));

        $this->assertEquals(200, $rss->getStatusCode());
        $this->assertEquals(200, $atom->getStatusCode());
        $this->assertStringContainsString('<rss version="2.0"', (string) $rss->getBody());
        $this->assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom"', (string) $atom->getBody());
    }
}
