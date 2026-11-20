<?php

namespace Cengizkemec\GoogleNews\Tests\unit;

use Cengizkemec\GoogleNews\Support\Url;
use Cengizkemec\GoogleNews\Support\Xml;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    #[Test]
    public function absolute_urls_are_validated(): void
    {
        $this->assertSame(
            'https://example.com/path',
            Url::absolute('https://example.com/path', 'https://forum.test')
        );
        $this->assertSame(
            'https://forum.test/path',
            Url::absolute('/path', 'https://forum.test')
        );
        $this->assertNull(Url::absolute('javascript:alert(1)', 'https://forum.test'));
        $this->assertNull(Url::absolute('//evil.test/path', 'https://forum.test'));
        $this->assertNull(Url::absolute('ftp://example.com/file', 'https://forum.test'));
    }

    #[Test]
    public function language_codes_are_normalised(): void
    {
        $this->assertSame('en', Url::language('EN'));
        $this->assertSame('tr', Url::language('tr'));
        $this->assertSame('pt-BR', Url::language('pt_br'));
        $this->assertSame('en', Url::language('not-a-language'));
        $this->assertSame('en', Url::language(null));
    }

    #[Test]
    public function xml_escaping_handles_special_characters_and_turkish_text(): void
    {
        $xml = '<title>'.Xml::escape('Türkçe <script> & "quotes" \'apostrophe\'').'</title>';

        $this->assertStringContainsString('Türkçe &lt;script&gt; &amp; &quot;quotes&quot; &apos;apostrophe&apos;', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    #[Test]
    public function invalid_xml_control_characters_are_removed(): void
    {
        $xml = '<title>'.Xml::escape("hello\x00\x01world").'</title>';

        $this->assertSame('<title>helloworld</title>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    #[Test]
    public function cdata_cannot_terminate_early(): void
    {
        $value = Xml::cdata('hello ]]> world');

        $this->assertStringContainsString(']]]]><![CDATA[>', $value);
    }
}
