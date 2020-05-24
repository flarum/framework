<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Event\GetPermission;
use Illuminate\Contracts\Events\Dispatcher;

class Gate
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param User $actor
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($actor, $ability, $arguments)
    {
        // Fire an event so that core and extension policies can hook into
        // this permission query and explicitly grant or deny the
        // permission.
        $allowed = $this->events->until(
            new GetPermission($actor, $ability, $arguments)
        );

        if (!is_null($allowed)) {
            return $allowed;
        }

        // If no policy covered this permission query, we will only grant
        // the permission if the actor's groups have it. Otherwise, we will
        // not allow the user to perform this action.
        if ($actor->isAdmin() || ($actor->hasPermission($ability))) {
            return true;
        }

        return false;
    }
}
