<?php namespace Flarum\Core\Discussions;

use Flarum\Core\Users\User;

interface DiscussionRepositoryInterface
{
    /**
     * Get a new query builder for the discussions table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query();

    /**
     * Find a discussion by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param integer $id
     * @param \Flarum\Core\Users\User $actor
     * @return \Flarum\Core\Discussions\Discussion
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null);

    /**
     * Get the IDs of discussions which a user has read completely.
     *
     * @param \Flarum\Core\Users\User $user
     * @return array
     */
    public function getReadIds(User $user);
}
