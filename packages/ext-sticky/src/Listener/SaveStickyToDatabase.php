<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Listener;

use Flarum\Discussion\Event\Saving;
use Flarum\Sticky\Event\DiscussionWasStickied;
use Flarum\Sticky\Event\DiscussionWasUnstickied;

class SaveStickyToDatabase
{
    /**
     * @param Saving $event
     */
    public function handle(Saving $event)
    {
        if (isset($event->data['attributes']['isSticky'])) {
            $isSticky = (bool) $event->data['attributes']['isSticky'];
            $discussion = $event->discussion;
            $actor = $event->actor;

            $actor->assertCan('sticky', $discussion);

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
