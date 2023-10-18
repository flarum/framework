<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Http\AccessToken;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class AccessTokenSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return AccessToken::query()->whereVisibleTo($actor);
    }
}
