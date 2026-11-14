<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Listener;

use Flarum\Messages\DialogMessage;
use Flarum\Messages\DialogMessage\Event\Liked;
use Flarum\Messages\DialogMessage\Event\Unliked;
use Flarum\Messages\Notification\MessageLikedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Tells a message's author it was liked, and withdraws that when the like is
 * taken back. Only an author still in the conversation, whom the liker may
 * write to, as for the message itself.
 */
class SendNotificationWhenMessageLiked
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Liked::class, $this->whenLiked(...));
        $events->listen(Unliked::class, $this->whenUnliked(...));
    }

    public function whenLiked(Liked $event): void
    {
        $author = $event->message->user;

        if (! $author || $author->is($event->user)) {
            return;
        }

        $recipients = $this->authorMayBeTold($event->message, $author, $event->user) ? [$author] : [];

        $this->notifications->sync(new MessageLikedBlueprint($event->message, $event->user), $recipients);
    }

    public function whenUnliked(Unliked $event): void
    {
        $author = $event->message->user;

        if (! $author || $author->is($event->user)) {
            return;
        }

        $this->notifications->sync(new MessageLikedBlueprint($event->message, $event->user), []);
    }

    protected function authorMayBeTold(DialogMessage $message, User $author, User $liker): bool
    {
        return $liker->can('message', $author)
            && DialogMessage::query()->whereVisibleTo($author)->whereKey($message->id)->exists();
    }
}
