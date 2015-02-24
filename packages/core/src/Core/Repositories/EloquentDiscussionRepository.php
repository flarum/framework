<?php namespace Flarum\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\User;

class EloquentDiscussionRepository implements DiscussionRepositoryInterface
{
    /**
     * Get a new query builder for ths discussions table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return Discussion::query();
    }

    /**
     * Find a discussion by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param  integer  $id
     * @param  \Flarum\Core\Models\User  $user
     * @return \Flarum\Core\Models\Discussion
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $user = null)
    {
        $query = Discussion::where('id', $id);

        return $this->scopeVisibleForUser($query, $user)->firstOrFail();
    }

    /**
     * Get the IDs of discussions which a user has read completely.
     *
     * @param  \Flarum\Core\Models\User  $user
     * @return array
     */
    public function getReadIds(User $user)
    {
        return Discussion::leftJoin('users_discussions', 'users_discussions.discussion_id', '=', 'discussions.id')
            ->where('user_id', $user->id)
            ->where('read_number', '<', 'last_post_number')
            ->lists('id');
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Flarum\Core\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeVisibleForUser(Builder $query, User $user = null)
    {
        if ($user !== null) {
            $query->whereCan($user, 'view');
        }

        return $query;
    }
}
