<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Filter\AbstractFilterer;
use Flarum\Http\AccessToken;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class AccessTokenFilterer extends AbstractFilterer
{
    protected function getQuery(User $actor): Builder
    {
        return AccessToken::query()->whereVisibleTo($actor);
    }
}
