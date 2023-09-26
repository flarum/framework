<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search;

use Flarum\Search\AbstractSearcher;
use Flarum\Search\FilterManager;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class UserSearcher extends AbstractSearcher
{
    protected function getQuery(User $actor): Builder
    {
        return User::whereVisibleTo($actor)->select('users.*');
    }
}
