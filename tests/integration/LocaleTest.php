<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use Flarum\Locale\TranslatorInterface;
use PHPUnit\Framework\Attributes\Test;

class LocaleTest extends GoogleNewsTestCase
{
    #[Test]
    public function english_and_turkish_translations_are_registered(): void
    {
        $translator = $this->app()->getContainer()->make(TranslatorInterface::class);
        $this->app()->getContainer()->make(\Flarum\Locale\LocaleManager::class);

        $this->assertSame('Home', $translator->trans('cengizkemec-google-news.lib.home'));
        $this->assertSame('Google News', $translator->trans('cengizkemec-google-news.admin.title'));

        $translator->setLocale('tr');

        $this->assertSame('Ana sayfa', $translator->trans('cengizkemec-google-news.lib.home'));
        $this->assertSame('Google Haberler', $translator->trans('cengizkemec-google-news.admin.title'));
    }
}
