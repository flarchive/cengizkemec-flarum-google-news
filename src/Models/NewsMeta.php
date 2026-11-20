<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Models;

use Cengizkemec\GoogleNews\Services\CacheService;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discussion_id
 * @property bool $is_news
 * @property bool $enable_structured_data
 * @property bool $include_in_sitemap
 * @property bool $include_in_feed
 * @property string|null $headline
 * @property string|null $description
 * @property string|null $category
 * @property string|null $language
 * @property string|null $cover_image
 * @property string|null $author_name
 * @property string|null $author_url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Discussion $discussion
 */
class NewsMeta extends AbstractModel
{
    protected $table = 'google_news_meta';

    public $timestamps = true;

    protected $fillable = [
        'discussion_id',
        'is_news',
        'enable_structured_data',
        'include_in_sitemap',
        'include_in_feed',
        'headline',
        'description',
        'category',
        'language',
        'cover_image',
        'author_name',
        'author_url',
    ];

    protected $casts = [
        'discussion_id' => 'integer',
        'is_news' => 'boolean',
        'enable_structured_data' => 'boolean',
        'include_in_sitemap' => 'boolean',
        'include_in_feed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function build(int $discussionId): static
    {
        $meta = new static();
        $meta->discussion_id = $discussionId;

        return $meta;
    }

    public static function booted(): void
    {
        static::saved(function () {
            if (class_exists(CacheService::class)) {
                resolve(CacheService::class)->flush();
            }
        });

        static::deleted(function () {
            if (class_exists(CacheService::class)) {
                resolve(CacheService::class)->flush();
            }
        });
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }
}
