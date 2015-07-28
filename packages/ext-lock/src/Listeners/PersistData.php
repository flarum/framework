<?php namespace Flarum\Lock\Listeners;

use Flarum\Lock\Events\DiscussionWasLocked;
use Flarum\Lock\Events\DiscussionWasUnlocked;
use Flarum\Events\DiscussionWillBeSaved;

class PersistData
{
    public function subscribe($events)
    {
        $events->listen(DiscussionWillBeSaved::class, [$this, 'whenDiscussionWillBeSaved']);
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if (isset($event->data['attributes']['isLocked'])) {
            $isLocked = (bool) $event->data['attributes']['isLocked'];
            $discussion = $event->discussion;
            $actor = $event->actor;

            $discussion->assertCan($actor, 'lock');

            if ((bool) $discussion->is_locked === $isLocked) {
                return;
            }

            $discussion->is_locked = $isLocked;

            $discussion->raise(
                $discussion->is_locked
                    ? new DiscussionWasLocked($discussion, $actor)
                    : new DiscussionWasUnlocked($discussion, $actor)
            );
        }
    }
}
