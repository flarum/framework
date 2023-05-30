<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Sticky\Event\DiscussionWasStickied;
use Flarum\Sticky\Event\DiscussionWasUnstickied;
use Flarum\Sticky\Post\DiscussionStickiedPost;
use Flarum\User\User;

class CreatePostWhenDiscussionIsStickied
{
    public static function whenDiscussionWasStickied(DiscussionWasStickied $event): void
    {
        static::stickyChanged($event->discussion, $event->user, true);
    }

    public static function whenDiscussionWasUnstickied(DiscussionWasUnstickied $event): void
    {
        static::stickyChanged($event->discussion, $event->user, false);
    }

    protected static function stickyChanged(Discussion $discussion, User $user, bool $isSticky): void
    {
        $post = DiscussionStickiedPost::reply(
            $discussion->id,
            $user->id,
            $isSticky
        );

        $discussion->mergePost($post);
    }
}
