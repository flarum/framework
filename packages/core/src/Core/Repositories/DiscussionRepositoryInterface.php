<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\User;

interface DiscussionRepositoryInterface
{
    /**
     * Get a new query builder for ths discussions table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query();

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
    public function findOrFail($id, User $user = null);

    /**
     * Get the IDs of discussions which a user has read completely.
     *
     * @param  \Flarum\Core\Models\User  $user
     * @return array
     */
    public function getReadIds(User $user);
}
