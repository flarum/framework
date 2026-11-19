<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Access;

use Flarum\Messages\DialogAudience;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeDialogVisibility
{
    public function __invoke(User $actor, Builder $query): void
    {
        DialogAudience::scope($query, (int) $actor->id);
    }
}
