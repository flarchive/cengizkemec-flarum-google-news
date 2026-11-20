<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api\Resource;

use Cengizkemec\GoogleNews\Models\NewsMeta;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<NewsMeta>
 */
class NewsMetaResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    public function type(): string
    {
        return 'googleNewsMeta';
    }

    public function model(): string
    {
        return NewsMeta::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->whereIn(
            'discussion_id',
            Discussion::whereVisibleTo($context->getActor())->select('id')
        );
    }

    public function newModel(OriginalContext $context): object
    {
        if ($context->creating(self::class) && ($discussionId = Arr::get($context->body(), 'data.relationships.discussion.data.id'))) {
            return NewsMeta::firstOrNew(['discussion_id' => (int) $discussionId]);
        }

        return parent::newModel($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->can('googleNews.manage')
                ->defaultInclude(['discussion']),
            Endpoint\Update::make()
                ->authenticated()
                ->can('update')
                ->defaultInclude(['discussion']),
            Endpoint\Show::make()
                ->authenticated()
                ->can('googleNews.manage')
                ->defaultInclude(['discussion']),
        ];
    }

    public function fields(): array
    {
        $canManage = fn (NewsMeta $meta, Context $context) => $context->getActor()->can('googleNews.manage');

        return [
            Schema\Boolean::make('isNews')
                ->writable($canManage)
                ->visible($canManage),
            Schema\Boolean::make('enableStructuredData')
                ->writable($canManage)
                ->visible($canManage),
            Schema\Boolean::make('includeInSitemap')
                ->writable($canManage)
                ->visible($canManage),
            Schema\Boolean::make('includeInFeed')
                ->writable($canManage)
                ->visible($canManage),

            Schema\Str::make('headline')
                ->nullable()
                ->maxLength(200)
                ->writable($canManage)
                ->visible($canManage),
            Schema\Str::make('description')
                ->nullable()
                ->maxLength(1000)
                ->writable($canManage)
                ->visible($canManage),
            Schema\Str::make('category')
                ->nullable()
                ->maxLength(150)
                ->writable($canManage)
                ->visible($canManage),
            Schema\Str::make('language')
                ->nullable()
                ->maxLength(20)
                ->rule($this->languageRule())
                ->writable($canManage)
                ->visible($canManage),
            Schema\Str::make('coverImage')
                ->nullable()
                ->maxLength(2048)
                ->rule($this->absoluteUrlRule())
                ->writable($canManage)
                ->visible($canManage),
            Schema\Str::make('authorName')
                ->nullable()
                ->maxLength(150)
                ->writable($canManage)
                ->visible($canManage),
            Schema\Str::make('authorUrl')
                ->nullable()
                ->maxLength(2048)
                ->rule($this->absoluteUrlRule())
                ->writable($canManage)
                ->visible($canManage),

            Schema\Relationship\ToOne::make('discussion')
                ->type('discussions')
                ->includable()
                ->requiredOnCreate()
                ->writableOnCreate()
                ->visible($canManage),
        ];
    }

    protected function languageRule(): callable
    {
        return fn () => function (string $attribute, mixed $value, \Closure $fail) {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value) || ! preg_match('/^[a-zA-Z]{2}(?:[-_][a-zA-Z]{2})?$/', $value)) {
                $fail($this->translator->trans('cengizkemec-google-news.lib.invalid_language'));
            }
        };
    }

    protected function absoluteUrlRule(): callable
    {
        // Flarum treats callable validation rules as factories: the outer
        // closure receives the API context and returns the actual Laravel
        // validation closure.
        return fn () => function (string $attribute, mixed $value, \Closure $fail) {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value)
                || ! preg_match('#^https?://#i', $value)
                || filter_var($value, FILTER_VALIDATE_URL) === false
            ) {
                $fail($this->translator->trans('cengizkemec-google-news.lib.invalid_url'));
            }
        };
    }
}
