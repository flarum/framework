<?php namespace Flarum\Sticky\Handlers;

use Flarum\Sticky\Events\DiscussionWasStickied;
use Flarum\Sticky\Events\DiscussionWasUnstickied;
use Flarum\Core\Events\DiscussionWillBeSaved;

class StickySaver
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionWillBeSaved', __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if (isset($event->command->data['isSticky'])) {
            $isSticky = (bool) $event->command->data['isSticky'];
            $discussion = $event->discussion;
            $user = $event->command->user;

            $discussion->assertCan($user, 'sticky');

            if ((bool) $discussion->is_sticky === $isSticky) {
                return;
            }

            $discussion->is_sticky = $isSticky;

            $discussion->raise(
                $discussion->is_sticky
                    ? new DiscussionWasStickied($discussion, $user)
                    : new DiscussionWasUnstickied($discussion, $user)
            );
        }
    }
}
