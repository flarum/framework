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
