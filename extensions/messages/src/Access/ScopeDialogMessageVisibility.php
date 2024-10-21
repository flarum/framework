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

class ScopeDialogMessageVisibility
{
    public function __invoke(User $actor, Builder $query): void
    {
        $query->whereHas('dialog', function (Builder $query) use ($actor) {
            $query->whereVisibleTo($actor);
        });
    }
}
