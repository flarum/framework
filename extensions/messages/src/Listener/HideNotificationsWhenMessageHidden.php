<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Listener;

use Flarum\Messages\DialogMessage;
use Flarum\Messages\DialogMessage\Event\Hidden;
use Flarum\Messages\DialogMessage\Event\Restored;
use Flarum\Messages\Notification\MessageLikedBlueprint;
use Flarum\Notification\Notification;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * The alerts about a message, that someone liked it, go while it is deleted.
 * Restoring it brings back those whose like is still there: one taken back
 * meanwhile, or before, stays gone.
 */
class HideNotificationsWhenMessageHidden
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Hidden::class, fn (Hidden $event) => $this->hide($event->message));
        $events->listen(Restored::class, fn (Restored $event) => $this->restore($event->message));
    }

    protected function hide(DialogMessage $message): void
    {
        $this->alerts($message)->update(['is_deleted' => true]);

        Notification::forgetCachedCounts([$message->user_id]);
    }

    protected function restore(DialogMessage $message): void
    {
        $this->alerts($message)
            ->whereIn('from_user_id', $message->getConnection()->table('dialog_message_likes')->where('dialog_message_id', $message->id)->select('user_id'))
            ->update(['is_deleted' => false]);

        Notification::forgetCachedCounts([$message->user_id]);
    }

    /**
     * Likes are alerted to the message's author, so their own alerts are the
     * ones to look through, not every notification on the forum.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Notification>
     */
    protected function alerts(DialogMessage $message): \Illuminate\Database\Eloquent\Builder
    {
        return Notification::query()
            ->where('user_id', $message->user_id)
            ->where('type', MessageLikedBlueprint::getType())
            ->where('subject_id', $message->id);
    }
}
