<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use PHPUnit\Framework\Attributes\Test;

class FofSeoCompatibilityTest extends GoogleNewsTestCase
{
    #[Test]
    public function our_metadata_defers_to_fof_seo_in_compatibility_mode(): void
    {
        $this->withBlog();
        $this->extension('fof-seo');

        $this->seedArticle([
            'blog' => true,
            'is_news' => true,
            'title' => 'FoF SEO compatibility article',
            'headline' => 'FoF SEO compatibility headline',
            'description' => 'Our description',
        ]);

        $response = $this->send($this->request('GET', '/blog/1-test-article'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());

        // FoF SEO owns the SEO output in compatibility mode: exactly one
        // structured-data block and one set of Open Graph tags.
        $this->assertEquals(1, substr_count($body, 'application/ld+json'));
        $this->assertEquals(1, substr_count($body, 'property="og:title"'));
        $this->assertEquals(1, substr_count($body, 'property="og:description"'));
        $this->assertEquals(1, substr_count($body, '<meta name="description"'));
        $this->assertEquals(1, substr_count($body, 'rel="canonical"'));
        $this->assertStringContainsString('rel="canonical" href="http://localhost/blog/1-test-article"', $body);
        $this->assertStringNotContainsString('"@type":"NewsArticle"', $body);
    }

    #[Test]
    public function disabling_compatibility_mode_allows_our_structured_data(): void
    {
        $this->withBlog();
        $this->extension('fof-seo');
        $this->setting('cengizkemec-google-news.schema.compatibility_mode', false);

        $this->seedArticle([
            'blog' => true,
            'is_news' => true,
            'title' => 'Forced structured data article',
        ]);

        $body = (string) $this->send($this->request('GET', '/blog/1-test-article'))->getBody();

        $this->assertStringContainsString('"@type":"NewsArticle"', $body);
        $this->assertGreaterThanOrEqual(1, substr_count($body, 'application/ld+json'));
    }
}
