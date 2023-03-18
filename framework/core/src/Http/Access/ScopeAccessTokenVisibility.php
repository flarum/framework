<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Access;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeAccessTokenVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->isGuest()) {
            $query->whereRaw('FALSE');
        } elseif (! $actor->hasPermission('moderateAccessTokens')) {
            $query->where('user_id', $actor->id);
        }
    }
}
