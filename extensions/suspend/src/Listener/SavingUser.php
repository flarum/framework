<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\User\Event\Saving;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;

class SavingUser
{
    public function __construct(
        protected Dispatcher $events
    ) {
    }

    public function handle(Saving $event): void
    {
        $user = $event->user;
        $actor = $event->actor;

        // An admin is never suspended, whatever any policy allows: the policy
        // only refuses it to actors who hold the permission to suspend.
        if ($user->isDirty('suspended_until') && $user->suspended_until !== null && $user->isAdmin()) {
            throw new PermissionDeniedException();
        }

        // When unsuspending, clear reason and message
        if ($user->isDirty('suspended_until') && $user->suspended_until === null) {
            $user->suspend_reason = null;
            $user->suspend_message = null;
        }

        if ($user->isDirty(['suspended_until', 'suspend_reason', 'suspend_message'])) {
            $this->events->dispatch(
                $user->suspended_until === null ?
                    new Unsuspended($user, $actor) :
                    new Suspended($user, $actor)
            );
        }
    }
}
