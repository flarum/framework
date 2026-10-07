<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class UserPolicy extends AbstractPolicy
{
    /**
     * Whether this member can be written to at all. Other extensions rule
     * people out with a policy of their own, which wins over this even for
     * admins: flarum/gdpr denies every ability on an anonymised account.
     * Whether the actor may send, and whether the member could reply, are
     * checked where messages are sent (MessagingPermission): a suspended
     * member answering a moderator still needs this to allow them.
     */
    public function message(User $actor, User $user): bool
    {
        return true;
    }
}
