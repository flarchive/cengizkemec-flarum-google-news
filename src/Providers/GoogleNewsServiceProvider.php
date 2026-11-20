<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Providers;

use Cengizkemec\GoogleNews\Integrations\DiscussionProvider;
use Cengizkemec\GoogleNews\Integrations\FofBlog\FofBlogProvider;
use Cengizkemec\GoogleNews\Integrations\ProviderRegistry;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Translation\MessageCatalogueInterface;

class GoogleNewsServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ProviderRegistry::class, function (Container $container) {
            return new ProviderRegistry(
                $container->make(FofBlogProvider::class),
                $container->make(DiscussionProvider::class)
            );
        });
    }

    public function boot(Container $container): void
    {
        // Register locale files here as well as through Extend\Locales. Tests
        // and long-running processes may resolve LocaleManager before the
        // extension's resolving callback is attached (for example while
        // enabling the extension), so this guarantees the admin and forum
        // strings are always available.
        $locales = $container->make(LocaleManager::class);
        $directory = dirname(__DIR__, 2).'/locale';

        if (! is_dir($directory)) {
            return;
        }

        foreach (new \DirectoryIterator($directory) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = $file->getExtension();

            if (! in_array($extension, ['yml', 'yaml'], true)) {
                continue;
            }

            $locale = str_replace(
                MessageCatalogueInterface::INTL_DOMAIN_SUFFIX,
                '',
                $file->getBasename('.'.$extension)
            );

            $locales->addTranslations($locale, $file->getPathname());
        }
    }
}
