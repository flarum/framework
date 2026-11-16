<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Access;

use Flarum\Messages\Dialog;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DialogPolicy extends AbstractPolicy
{
    public function view(User $actor, Dialog $dialog): bool
    {
        return Dialog::whereVisibleTo($actor)->where('id', $dialog->id)->exists();
    }

    /**
     * Only a group: leaving a direct conversation would leave nobody in it.
     */
    public function leave(User $actor, Dialog $dialog): bool
    {
        return $dialog->isGroup() && $this->view($actor, $dialog);
    }

    public function sendMessage(User $actor, Dialog $dialog): bool
    {
        return $this->view($actor, $dialog) && MessagingPermission::canSendIn($actor, $dialog);
    }
}
