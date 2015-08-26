<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Support;

use Flarum\Events\ScopeModelVisibility;
use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Users\User;

trait VisibleScope
{
    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $actor
     */
    public function scopeWhereVisibleTo(Builder $query, User $actor)
    {
        if (! app('flarum.forum')->can($actor, 'view')) {
            if ($this instanceof User) {
                $query->where('id', $actor->id);
            } else {
                $query->whereRaw('FALSE');
            }
        } else {
            event(new ScopeModelVisibility($this, $query, $actor));
        }
    }
}
