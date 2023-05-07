<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Illuminate\Database\Eloquent\Builder;

class UserRepository
{
    /**
     * Get a new query builder for the users table.
     *
     * @return Builder<User>
     */
    public function query()
    {
        return User::query();
    }

    /**
     * Find a user by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param int|string $id
     * @param User|null $actor
     * @return User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find a user by username, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param string $username
     * @param User|null $actor
     * @return User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailByUsername($username, User $actor = null)
    {
        $query = $this->query()->where('username', $username);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find a user by an identification (username or email).
     *
     * @param string $identification
     * @return User|null
     */
    public function findByIdentification($identification)
    {
        $field = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return $this->query()->where($field, $identification)->first();
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail($email)
    {
        return $this->query()->where('email', $email)->first();
    }

    /**
     * Get the ID of a user with the given username.
     *
     * @param string $username
     * @param User|null $actor
     * @return int|null
     */
    public function getIdForUsername($username, User $actor = null)
    {
        $query = $this->query()->where('username', $username);

        return $this->scopeVisibleTo($query, $actor)->value('id');
    }

    public function getIdsForUsernames(array $usernames, User $actor = null): array
    {
        $query = $this->query()->whereIn('username', $usernames);

        return $this->scopeVisibleTo($query, $actor)->pluck('id')->all();
    }

    /**
     * Find users by matching a string of words against their username,
     * optionally making sure they are visible to a certain user.
     *
     * @param string $string
     * @param User|null $actor
     * @return array
     */
    public function getIdsForUsername($string, User $actor = null)
    {
        $string = $this->escapeLikeString($string);

        $query = $this->query()->where('username', 'like', '%'.$string.'%')
            ->orderByRaw('username = ? desc', [$string])
            ->orderByRaw('username like ? desc', [$string.'%']);

        return $this->scopeVisibleTo($query, $actor)->pluck('id')->all();
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder<User> $query
     * @param User|null $actor
     * @return Builder<User>
     */
    protected function scopeVisibleTo(Builder $query, User $actor = null)
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }

    /**
     * Escape special characters that can be used as wildcards in a LIKE query.
     *
     * @param string $string
     * @return string
     */
    private function escapeLikeString($string)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $string);
    }
}
