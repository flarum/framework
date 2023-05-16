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
     * Get a new query builder for the groups table.
     *
     * @return Builder<Group>
     */
    public function query()
    {
        return Group::query();
    }

    /**
     * Find a user by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param int $id
     * @param User|null $actor
     * @return Group
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    public function queryVisibleTo(?User $actor = null)
    {
        return $this->scopeVisibleTo($this->query(), $actor);
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder<Group> $query
     * @param User|null $actor
     * @return Builder<Group>
     */
    protected function scopeVisibleTo(Builder $query, ?User $actor = null)
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }
}
