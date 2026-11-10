<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Listener;

use Flarum\OAuthProvider\Event\DisconnectedAll;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\User\Event\PasswordChanged;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Changing a password is, like logging out everywhere, what a member does when
 * they fear for their account. Core revokes their access tokens on this event;
 * we match it by disconnecting the applications they connected, so a token a
 * leaked password bought stops working too.
 */
class DisconnectApplicationsOnPasswordChange
{
    public function __construct(
        protected Dispatcher $events,
    ) {
    }

    public function handle(PasswordChanged $event): void
    {
        if (Consent::revokeAllFor((int) $event->user->id)) {
            $this->events->dispatch(new DisconnectedAll($event->user, DisconnectedAll::PASSWORD_CHANGE));
        }
    }
}
