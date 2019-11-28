<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;

trait AssertPermissionTrait
{
    /**
     * Ensure the current user is allowed to do something.
     *
     * If the condition is not met, an exception will be thrown that signals the
     * lack of permissions. This is about *authorization*, i.e. retrying such a
     * request / operation without a change in permissions (or using another
     * user account) is pointless.
     *
     * @param bool $condition
     * @throws PermissionDeniedException
     */
    protected function assertPermission($condition)
    {
        if (! $condition) {
            throw new PermissionDeniedException;
        }
    }

    /**
     * Ensure the given actor is authenticated.
     *
     * This will throw an exception for guest users, signaling that
     * *authorization* failed. Thus, they could retry the operation after
     * logging in (or using other means of authentication).
     *
     * @param User $actor
     * @throws NotAuthenticatedException
     */
    protected function assertRegistered(User $actor)
    {
        if ($actor->isGuest()) {
            throw new NotAuthenticatedException;
        }
    }

    /**
     * @param User $actor
     * @param string $ability
     * @param mixed $arguments
     * @throws PermissionDeniedException
     */
    protected function assertCan(User $actor, $ability, $arguments = [])
    {
        $this->assertPermission(
            $actor->can($ability, $arguments)
        );
    }

    /**
     * @param User $actor
     * @throws PermissionDeniedException
     */
    protected function assertAdmin(User $actor)
    {
        $this->assertCan($actor, 'administrate');
    }
}
