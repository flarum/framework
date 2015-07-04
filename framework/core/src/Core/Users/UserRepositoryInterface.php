<?php namespace Flarum\Core\Users;

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
     * @param int $id
     * @param User $actor
     * @return User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null);

    /**
     * Find a user by an identification (username or email).
     *
     * @param string $identification
     * @return User|null
     */
    public function findByIdentification($identification);

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail($email);

    /**
     * Get the ID of a user with the given username.
     *
     * @param string $username
     * @param User|null $actor
     * @return integer|null
     */
    public function getIdForUsername($username, User $actor = null);

    /**
     * Find users by matching a string of words against their username,
     * optionally making sure they are visible to a certain user.
     *
     * @param string $string
     * @param User|null $actor
     * @return array
     */
    public function getIdsForUsername($string, User $actor = null);
}
