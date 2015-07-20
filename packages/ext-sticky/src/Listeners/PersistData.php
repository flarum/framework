<?php namespace Flarum\Sticky\Listeners;

use Flarum\Sticky\Events\DiscussionWasStickied;
use Flarum\Sticky\Events\DiscussionWasUnstickied;
use Flarum\Events\DiscussionWillBeSaved;

class PersistData
{
    public function subscribe($events)
    {
        $events->listen(DiscussionWillBeSaved::class, __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if (isset($event->data['attributes']['isSticky'])) {
            $isSticky = (bool) $event->data['attributes']['isSticky'];
            $discussion = $event->discussion;
            $actor = $event->actor;

            $discussion->assertCan($actor, 'sticky');

            if ((bool) $discussion->is_sticky === $isSticky) {
                return;
            }

            $discussion->is_sticky = $isSticky;

            $discussion->raise(
                $discussion->is_sticky
                    ? new DiscussionWasStickied($discussion, $actor)
                    : new DiscussionWasUnstickied($discussion, $actor)
            );
        }
    }
}
