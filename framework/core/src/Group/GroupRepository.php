<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class GroupRepository
{
    /**
     * @return Builder<Group>
     */
    public function query(): Builder
    {
        return Group::query();
    }

    /**
     * Find a user by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string|int $id, ?User $actor = null): Group
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    public function queryVisibleTo(?User $actor = null): Builder
    {
        return $this->scopeVisibleTo($this->query(), $actor);
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder<Group> $query
     * @return Builder<Group>
     */
    protected function scopeVisibleTo(Builder $query, ?User $actor = null): Builder
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }
}
