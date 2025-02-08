<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Access;

use Carbon\Carbon;
use Flarum\Messages\DialogMessage;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DialogMessagePolicy extends AbstractPolicy
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function update(User $actor, DialogMessage $message): ?bool
    {
        return null;
    }

    public function delete(User $actor, DialogMessage $message): bool|null|string
    {
        if ($message->user_id === $actor->id) {
            $allowHiding = $this->settings->get('flarum-messages.allow_delete_own_messages');

            if ($allowHiding === '-1'
                || ($allowHiding === 'reply' && $message->number >= $message->dialog->lastMessage->number)
                || (is_numeric($allowHiding) && $message->created_at->diffInMinutes(new Carbon, true) < $allowHiding)) {
                return $this->allow();
            }
        }

        return false;
    }
}
