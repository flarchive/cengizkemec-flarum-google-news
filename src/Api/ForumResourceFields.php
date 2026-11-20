<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api;

use Cengizkemec\GoogleNews\Services\PublisherConfig;
use Cengizkemec\GoogleNews\Services\Settings;
use Flarum\Api\Context;
use Flarum\Api\Schema;

class ForumResourceFields
{
    public function __construct(
        protected Settings $settings,
        protected PublisherConfig $publisher
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canManageGoogleNews')
                ->get(fn ($model, Context $context) => $context->getActor()->can('googleNews.manage')),
            Schema\Boolean::make('googleNewsEnabled')
                ->get(fn () => $this->settings->isEnabled()),
            Schema\Str::make('googleNewsPublisherLogoUrl')
                ->visible(fn ($model, Context $context) => $context->getActor()->can('administrate'))
                ->get(fn () => $this->publisher->logoUrl()),
            Schema\Arr::make('googleNewsDefaults')
                ->visible(fn ($model, Context $context) => $context->getActor()->can('googleNews.manage'))
                ->get(fn () => [
                    'isNews' => $this->settings->bool('defaults.is_news', false),
                    'enableStructuredData' => $this->settings->bool('defaults.enable_structured_data', false),
                    'includeInSitemap' => $this->settings->bool('defaults.include_in_sitemap', false),
                    'includeInFeed' => $this->settings->bool('defaults.include_in_feed', true),
                    'language' => $this->settings->defaultLanguage() ?? '',
                ]),
        ];
    }
}
