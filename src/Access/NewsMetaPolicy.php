<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Access;

use Cengizkemec\GoogleNews\Models\NewsMeta;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class NewsMetaPolicy extends AbstractPolicy
{
    public function update(User $actor, NewsMeta $meta): ?string
    {
        return $actor->can('googleNews.manage') ? $this->allow() : null;
    }

    public function delete(User $actor, NewsMeta $meta): ?string
    {
        return $actor->can('googleNews.manage') ? $this->allow() : null;
    }
}
