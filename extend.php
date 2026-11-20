<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cengizkemec\GoogleNews\Access\NewsMetaPolicy;
use Cengizkemec\GoogleNews\Api\Controller\AtomFeedController;
use Cengizkemec\GoogleNews\Api\Controller\ClearCacheController;
use Cengizkemec\GoogleNews\Api\Controller\ContentSitemapController;
use Cengizkemec\GoogleNews\Api\Controller\DeletePublisherLogoController;
use Cengizkemec\GoogleNews\Api\Controller\NewsSitemapController;
use Cengizkemec\GoogleNews\Api\Controller\RssFeedController;
use Cengizkemec\GoogleNews\Api\Controller\StatusController;
use Cengizkemec\GoogleNews\Api\Controller\UploadPublisherLogoController;
use Cengizkemec\GoogleNews\Api\ForumResourceFields;
use Cengizkemec\GoogleNews\Api\Resource\NewsMetaResource;
use Cengizkemec\GoogleNews\Content\StructuredDataInjector;
use Cengizkemec\GoogleNews\Listeners\CacheInvalidationSubscriber;
use Cengizkemec\GoogleNews\Models\NewsMeta;
use Cengizkemec\GoogleNews\Providers\GoogleNewsServiceProvider;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;

return [
    (new Extend\ServiceProvider())
        ->register(GoogleNewsServiceProvider::class),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->content(StructuredDataInjector::class, -100),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Routes('forum'))
        ->get('/news-sitemap.xml', 'google-news.news-sitemap', NewsSitemapController::class)
        ->get('/google-news-sitemap.xml', 'google-news.content-sitemap', ContentSitemapController::class)
        ->get('/news-feed.xml', 'google-news.rss', RssFeedController::class)
        ->get('/news-atom.xml', 'google-news.atom', AtomFeedController::class),

    (new Extend\Routes('api'))
        ->get('/google-news/status', 'google-news.status', StatusController::class)
        ->delete('/google-news/cache', 'google-news.cache.clear', ClearCacheController::class)
        ->post('/google-news/logo', 'google-news.logo.upload', UploadPublisherLogoController::class)
        ->delete('/google-news/logo', 'google-news.logo.delete', DeletePublisherLogoController::class),

    new Extend\ApiResource(NewsMetaResource::class),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(fn () => [
            Schema\Relationship\ToOne::make('googleNewsMeta')
                ->type('googleNewsMeta')
                ->includable()
                ->visible(fn (Discussion $discussion, \Flarum\Api\Context $context) => $context->getActor()->can('googleNews.manage')),
        ])
        ->endpoint(
            [Endpoint\Show::class, Endpoint\Create::class, Endpoint\Update::class],
            fn (Endpoint\Show|Endpoint\Create|Endpoint\Update $endpoint) => $endpoint
                ->addDefaultInclude(['googleNewsMeta'])
        ),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(ForumResourceFields::class),

    (new Extend\Model(Discussion::class))
        ->hasOne('googleNewsMeta', NewsMeta::class, 'discussion_id'),

    (new Extend\Policy())
        ->modelPolicy(NewsMeta::class, NewsMetaPolicy::class),

    (new Extend\Event())
        ->subscribe(CacheInvalidationSubscriber::class),

    (new Extend\Settings())
        ->default('cengizkemec-google-news.enabled', true)
        ->default('cengizkemec-google-news.fof_blog_integration', true)
        ->default('cengizkemec-google-news.default_language', '')
        ->default('cengizkemec-google-news.publisher_name', '')
        ->default('cengizkemec-google-news.publisher_website', '')
        ->default('cengizkemec-google-news.publisher_country', '')
        ->default('cengizkemec-google-news.publisher_logo', '')
        ->default('cengizkemec-google-news.publisher_logo_url', '')
        ->default('cengizkemec-google-news.default_author', '')
        ->default('cengizkemec-google-news.sitemap.enabled', true)
        ->default('cengizkemec-google-news.sitemap.content_enabled', true)
        ->default('cengizkemec-google-news.sitemap.cache_duration', 900)
        ->default('cengizkemec-google-news.sitemap.news_window_hours', 48)
        ->default('cengizkemec-google-news.sitemap.max_news_entries', 1000)
        ->default('cengizkemec-google-news.sitemap.content_max_urls', 5000)
        ->default('cengizkemec-google-news.feeds.rss_enabled', true)
        ->default('cengizkemec-google-news.feeds.atom_enabled', true)
        ->default('cengizkemec-google-news.feeds.limit', 50)
        ->default('cengizkemec-google-news.feeds.description', '')
        ->default('cengizkemec-google-news.feeds.include_regular_posts', true)
        ->default('cengizkemec-google-news.schema.news_article', true)
        ->default('cengizkemec-google-news.schema.blog_posting', true)
        ->default('cengizkemec-google-news.schema.article', true)
        ->default('cengizkemec-google-news.schema.organization', true)
        ->default('cengizkemec-google-news.schema.website', true)
        ->default('cengizkemec-google-news.schema.breadcrumb', true)
        ->default('cengizkemec-google-news.schema.compatibility_mode', true)
        ->default('cengizkemec-google-news.seo.open_graph', true)
        ->default('cengizkemec-google-news.seo.open_graph_type', 'article')
        ->default('cengizkemec-google-news.seo.twitter', true)
        ->default('cengizkemec-google-news.seo.canonical', true)
        ->default('cengizkemec-google-news.seo.description', true)
        ->default('cengizkemec-google-news.seo.robots', 'index,follow')
        ->default('cengizkemec-google-news.defaults.is_news', false)
        ->default('cengizkemec-google-news.defaults.enable_structured_data', false)
        ->default('cengizkemec-google-news.defaults.include_in_sitemap', false)
        ->default('cengizkemec-google-news.defaults.include_in_feed', true),
];
