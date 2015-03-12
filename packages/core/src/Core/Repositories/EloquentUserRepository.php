<?php namespace Flarum\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * Get a new query builder for the users table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return User::query();
    }

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
    public function findOrFail($id, User $user = null)
    {
        $query = User::where('id', $id);

        return $this->scopeVisibleForUser($query, $user)->firstOrFail();
    }

    /**
     * Find a user by an identification (username or email).
     *
     * @param  string  $identification
     * @return \Flarum\Core\Models\User|null
     */
    public function findByIdentification($identification)
    {
        $field = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return User::where($field, $identification)->first();
    }

    /**
     * Get the ID of a user with the given username.
     *
     * @param  string  $username
     * @param  \Flarum\Core\Models\User  $user
     * @return integer|null
     */
    public function getIdForUsername($username, User $user = null)
    {
        $query = User::where('username', 'like', $username);

        return $this->scopeVisibleForUser($query, $user)->pluck('id');
    }

    /**
     * Find users by matching a string of words against their username,
     * optionally making sure they are visible to a certain user.
     *
     * @param  string  $string
     * @param  \Flarum\Core\Models\User|null  $user
     * @return array
     */
    public function getIdsForUsername($string, User $user = null)
    {
        $query = User::select('id')
            ->where('username', 'like', '%'.$string.'%')
            ->orderByRaw('username = ? desc', [$string])
            ->orderByRaw('username like ? desc', [$string.'%']);

        return $this->scopeVisibleForUser($query, $user)->lists('id');
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
