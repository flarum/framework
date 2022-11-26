<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class DiscussionRepository
{
    /**
     * Get a new query builder for the discussions table.
     *
     * @return Builder<Discussion>
     */
    public function query()
    {
        return Discussion::query();
    }

    /**
     * Find a discussion by ID, optionally making sure it is visible to a
     * certain user, or throw an exception.
     *
     * @param int|string $id
     * @param User|null $user
     * @return \Flarum\Discussion\Discussion
     */
    public function findOrFail($id, User $user = null)
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $user)->firstOrFail();
    }

    /**
     * Get the IDs of discussions which a user has read completely.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection<Discussion>
     * @deprecated 1.3 Use `getReadIdsQuery` instead
     */
    public function getReadIds(User $user)
    {
        return $this->getReadIdsQuery($user)->get();
    }

    /**
     * Get a query containing the IDs of discussions which a user has read completely.
     *
     * @param User $user
     * @return Builder<Discussion>
     */
    public function getReadIdsQuery(User $user): Builder
    {
        return $this->query()
            ->leftJoin('discussion_user', 'discussion_user.discussion_id', '=', 'discussions.id')
            ->where('discussion_user.user_id', $user->id)
            ->whereColumn('last_read_post_number', '>=', 'last_post_number')
            ->select('id');
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder<Discussion> $query
     * @param User|null $user
     * @return Builder<Discussion>
     */
    protected function scopeVisibleTo(Builder $query, User $user = null)
    {
        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }
}
