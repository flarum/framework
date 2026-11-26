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
    public function __invoke(User $actor, Builder $query): void
    {
        if ($actor->cannot('viewForum')) {
            if ($actor->isGuest()) {
                $query->whereRaw('FALSE');
            } else {
                // Qualified: an unqualified `id` is ambiguous on joined queries.
                $query->whereKey($actor->id);
            }
        }
    }
}
