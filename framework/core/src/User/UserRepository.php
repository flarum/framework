<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of User
 */
class UserRepository
{
    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        return User::query();
    }

    /**
     * Find a user by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @return T
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id, User $actor = null): Model
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find a user by username, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @return T
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailByUsername(string $username, User $actor = null): Model
    {
        $query = $this->query()->where('username', $username);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find a user by an identification (username or email).
     *
     * @return ?T
     */
    public function findByIdentification(string $identification): ?Model
    {
        $field = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return $this->query()->where($field, $identification)->first();
    }

    /**
     * @return ?T
     */
    public function findByEmail(string $email): ?Model
    {
        return $this->query()->where('email', $email)->first();
    }

    public function getIdForUsername(string $username, User $actor = null): ?int
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
     */
    public function getIdsForUsername(string $string, User $actor = null): array
    {
        $string = $this->escapeLikeString($string);

        $query = $this->query()->where('username', 'like', '%'.$string.'%')
            ->orderByRaw('username = ? desc', [$string])
            ->orderByRaw('username like ? desc', [$string.'%']);

        return $this->scopeVisibleTo($query, $actor)->pluck('id')->all();
    }

    /**
     * @return Builder<User>
     */
    protected function scopeVisibleTo(Builder $query, User $actor = null): Builder
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }

    /**
     * Escape special characters that can be used as wildcards in a LIKE query.
     */
    private function escapeLikeString(string $string): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $string);
    }
}
