<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Event\ScopeModelVisibility;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopeVisibilityTrait
{
    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $actor
     */
    public function scopeWhereVisibleTo(Builder $query, User $actor)
    {
        static::$dispatcher->dispatch(
            new ScopeModelVisibility($query, $actor, 'view')
        );
    }
}
