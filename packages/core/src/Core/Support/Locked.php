<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Support;

use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;
use Flarum\Events\ModelAllow;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * 'Lock' an object, allowing the permission of a user to perform an action to
 * be tested.
 */
trait Locked
{
    /**
     * Check whether or not a user has permission to perform an action,
     * according to the collected conditions.
     *
     * @param User $actor
     * @param string $action
     * @return bool
     */
    public function can(User $actor, $action)
    {
        $allowed = static::$dispatcher->until(new ModelAllow($this, $actor, $action));

        return $allowed ?: false;
    }

    /**
     * Assert that the user has a certain permission for this model, throwing
     * an exception if they don't.
     *
     * @param User $actor
     * @param string $action
     * @throws PermissionDeniedException
     */
    public function assertCan(User $actor, $action)
    {
        if (! $this->can($actor, $action)) {
            throw new PermissionDeniedException;
        }
    }
}
