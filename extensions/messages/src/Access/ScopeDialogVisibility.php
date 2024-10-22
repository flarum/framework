<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Access;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeDialogVisibility
{
    public function __invoke(User $actor, Builder $query): void
    {
        $query->whereHas('users', function (Builder $query) use ($actor) {
            $query->where('user_id', $actor->id);
        });
    }
}
