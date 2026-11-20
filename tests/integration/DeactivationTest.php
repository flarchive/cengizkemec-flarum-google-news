<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DeactivationTest extends TestCase
{
    #[Test]
    public function public_routes_are_not_available_when_the_extension_is_disabled(): void
    {
        $this->assertEquals(404, $this->send($this->request('GET', '/news-sitemap.xml'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/news-feed.xml'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/news-atom.xml'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/google-news-sitemap.xml'))->getStatusCode());
    }
}
