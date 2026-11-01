<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Support;

use Flarum\Http\SlugManager;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

/**
 * Finds the member a tool argument names. Tools take usernames, but the API
 * and core's filters take slugs, which depend on the forum's user slug driver.
 *
 * @internal
 */
class Members
{
    public function __construct(
        protected UserRepository $users,
        protected SlugManager $slugs,
    ) {
    }

    /**
     * The member with this username or, failing that, this slug, such as one
     * from a profile URL. Null when the actor can't see them.
     */
    public function find(string $usernameOrSlug, User $actor): ?User
    {
        $id = $this->users->getIdForUsername($usernameOrSlug, $actor);

        if ($id !== null) {
            return $this->users->findOrFail($id, $actor);
        }

        // A driver may read the slug as something it is not, such as a
        // username as a numeric ID, which PostgreSQL refuses outright. The
        // savepoint keeps that failure from aborting the request's transaction.
        try {
            return $this->users->query()->getConnection()->transaction(
                fn () => $this->slugs->forResource(User::class)->fromSlug($usernameOrSlug, $actor)
            );
        } catch (ModelNotFoundException|QueryException) {
            return null;
        }
    }

    public function slug(User $user): string
    {
        return $this->slugs->forResource(User::class)->toSlug($user);
    }
}
