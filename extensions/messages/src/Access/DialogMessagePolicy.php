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

    /**
     * With flarum/likes. Only members see a message at all, so this decides
     * which of them may like it: what they wrote, not a notice, under the same
     * rules as liking posts. A like answers a message, so only a member who
     * may reply in the conversation may like in it.
     */
    public function like(User $actor, DialogMessage $message): bool
    {
        if (! $message->isComment() || ! $actor->hasPermission('discussion.likePosts')) {
            return false;
        }

        if ($actor->id === $message->user_id && ! (bool) $this->settings->get('flarum-likes.like_own_post')) {
            return false;
        }

        return $message->dialog !== null && MessagingPermission::canSendIn($actor, $message->dialog);
    }

    public function delete(User $actor, DialogMessage $message): bool|null|string
    {
        // A notice records what happened; it isn't its member's to take back.
        if ($message->user_id === $actor->id && $message->isComment()) {
            $allowHiding = $this->settings->get('flarum-messages.allow_delete_own_messages');

            if ($allowHiding === '-1'
                || ($allowHiding === 'reply' && $message->id === $message->dialog?->last_message_id)
                || (is_numeric($allowHiding) && $message->created_at->diffInMinutes(new Carbon, true) < $allowHiding)) {
                return $this->allow();
            }
        }

        return false;
    }
}
