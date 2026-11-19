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

    /** Members' windows for their own messages, as the admin sets them on the Permissions page. */
    public const EDIT_OWN = 'flarum-messages.allow_edit_own_messages';

    public const DELETE_OWN = 'flarum-messages.allow_delete_own_messages';

    /** Deleting any message for good, rather than in a way that can be undone. */
    public const DELETE_PERMISSION = 'dialog.deleteMessages';

    /**
     * Members edit what they wrote, for as long as the forum allows. Nothing
     * here refuses anyone: other policies may allow what this one doesn't.
     */
    public function edit(User $actor, DialogMessage $message): ?string
    {
        if ($this->ownComment($actor, $message)
            && ! $message->isHidden()
            && $this->withinWindow($message, self::EDIT_OWN)
            && $this->canStillSend($actor, $message)) {
            return $this->allow();
        }

        return null;
    }

    /**
     * Delete in a way that can be undone, and restore. A member's own message,
     * unless someone else deleted it, for as long as the forum allows.
     */
    public function hide(User $actor, DialogMessage $message): ?string
    {
        if ($this->ownComment($actor, $message)
            && (! $message->isHidden() || $message->hidden_user_id === $actor->id)
            && $this->withinWindow($message, self::DELETE_OWN)
            && $this->canStillSend($actor, $message)) {
            return $this->allow();
        }

        return null;
    }

    /** Delete for good: a permission of its own, which no group has to begin with. */
    public function delete(User $actor, DialogMessage $message): ?string
    {
        if ($actor->hasPermission(self::DELETE_PERMISSION)) {
            return $this->allow();
        }

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
        if (! $message->isComment() || $message->isHidden() || ! $actor->hasPermission('discussion.likePosts')) {
            return false;
        }

        if ($actor->id === $message->user_id && ! (bool) $this->settings->get('flarum-likes.like_own_post')) {
            return false;
        }

        return $message->dialog !== null && MessagingPermission::canSendIn($actor, $message->dialog);
    }

    /** A notice records what happened; it isn't its member's to change. */
    protected function ownComment(User $actor, DialogMessage $message): bool
    {
        return $message->user_id === $actor->id && $message->isComment();
    }

    /**
     * Always (-1), for a number of minutes, until the message is no longer
     * the latest in its conversation (reply), or never (0, or nothing set).
     */
    protected function withinWindow(DialogMessage $message, string $setting): bool
    {
        $window = (string) $this->settings->get($setting);

        return $window === '-1'
            || ($window === 'reply' && $message->id === $message->dialog?->last_message_id)
            || (is_numeric($window) && (int) $window > 0 && $message->created_at->diffInMinutes(new Carbon, true) < (int) $window);
    }

    /** Someone who may no longer write in the conversation can't change what they wrote there. */
    protected function canStillSend(User $actor, DialogMessage $message): bool
    {
        return $message->dialog !== null && MessagingPermission::canSendIn($actor, $message->dialog);
    }
}
