<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Access;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeGroupVisibility
{
    public function __invoke(User $actor, Builder $query): void
    {
        if ($actor->cannot('viewHiddenGroups')) {
            $query->where('is_hidden', false);
        }
    }
}
