<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Lock\Listener;

use Flarum\Discussion\Event\Saving;
use Flarum\Lock\Event\DiscussionWasLocked;
use Flarum\Lock\Event\DiscussionWasUnlocked;
use Flarum\User\AssertPermissionTrait;

class SaveLockedToDatabase
{
    use AssertPermissionTrait;

    public function handle(Saving $event)
    {
        if (isset($event->data['attributes']['isLocked'])) {
            $isLocked = (bool) $event->data['attributes']['isLocked'];
            $discussion = $event->discussion;
            $actor = $event->actor;

            $this->assertCan($actor, 'lock', $discussion);

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
