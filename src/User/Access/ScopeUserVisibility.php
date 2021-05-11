<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Access;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeUserVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->cannot('viewForum')) {
            if ($actor->isGuest()) {
                $query->whereRaw('FALSE');
            } else {
                $query->where('id', $actor->id);
            }
        }
    }
}
