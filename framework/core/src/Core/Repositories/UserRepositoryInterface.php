<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\User;

interface UserRepositoryInterface
{
    /**
     * Get a new query builder for the users table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query();

    /**
     * Find a user by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param  integer  $id
     * @param  \Flarum\Core\Models\User  $user
     * @return \Flarum\Core\Models\User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $user = null);

    /**
     * Find a user by an identification (username or email).
     *
     * @param  string  $identification
     * @return \Flarum\Core\Models\User|null
     */
    public function findByIdentification($identification);

    /**
     * Get the ID of a user with the given username.
     *
     * @param  string  $username
     * @param  \Flarum\Core\Models\User  $user
     * @return integer|null
     */
    public function getIdForUsername($username, User $user = null);

    /**
     * Find users by matching a string of words against their username,
     * optionally making sure they are visible to a certain user.
     *
     * @param  string  $string
     * @param  \Flarum\Core\Models\User|null  $user
     * @return array
     */
    public function getIdsForUsername($string, User $user = null);
}
