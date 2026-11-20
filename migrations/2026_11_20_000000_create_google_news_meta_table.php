<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if ($schema->hasTable('google_news_meta')) {
            return;
        }

        $schema->create('google_news_meta', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('discussion_id')->unique();

            $table->boolean('is_news')->default(false);
            $table->boolean('enable_structured_data')->default(true);
            $table->boolean('include_in_sitemap')->default(false);
            $table->boolean('include_in_feed')->default(true);

            $table->string('headline', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('category', 150)->nullable();
            $table->string('language', 20)->nullable();
            $table->string('cover_image', 2048)->nullable();
            $table->string('author_name', 150)->nullable();
            $table->string('author_url', 2048)->nullable();

            $table->timestamps();

            $table->foreign('discussion_id')
                ->references('id')
                ->on('discussions')
                ->onDelete('cascade');
        });
    },
    'down' => function (Builder $schema) {
        if ($schema->hasTable('google_news_meta')) {
            $schema->drop('google_news_meta');
        }
    },
];
