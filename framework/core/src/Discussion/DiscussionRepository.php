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
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DiscussionRepository
{
    /**
     * @return Builder<Discussion>
     */
    public function query(): Builder
    {
        return Discussion::query();
    }

    /**
     * Find a discussion by ID, optionally making sure it is visible to a
     * certain user, or throw an exception.
     */
    public function findOrFail(int|string $id, ?User $user = null): Discussion
    {
        // Ids come from URLs and slugs too. Text such as "2abc" is no id:
        // MySQL would read it as 2, and PostgreSQL refuses to compare it at all.
        if (! ctype_digit((string) $id)) {
            throw (new ModelNotFoundException())->setModel(Discussion::class, [$id]);
        }

        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $user)->firstOrFail();
    }

    /**
     * Get a query containing the IDs of discussions which a user has read completely.
     *
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
     * @return Builder<Discussion>
     */
    protected function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }
}
