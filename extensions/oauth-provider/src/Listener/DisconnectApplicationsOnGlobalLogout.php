<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Listener;

use Flarum\OAuthProvider\Models\Consent;
use Flarum\User\Event\LoggedOut;

/**
 * Logging out everywhere is what a member does when they fear for their
 * account: it disconnects the applications they connected too, as it revokes
 * their developer tokens. Logging out of one browser leaves them be.
 */
class DisconnectApplicationsOnGlobalLogout
{
    public function handle(LoggedOut $event): void
    {
        if ($event->isGlobal) {
            Consent::revokeAllFor((int) $event->user->id);
        }
    }
}
