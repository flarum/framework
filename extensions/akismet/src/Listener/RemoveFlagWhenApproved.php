<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Flags\Event\Cleared;
use Flarum\Flags\Flag;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;

/**
 * On save rather than PostWasApproved: hiding a held post approves it without that event.
 */
class RemoveFlagWhenApproved
{
    public function handle(Saving $event): void
    {
        if (! $event->post->exists || $event->post->getOriginal('is_approved')) {
            return;
        }

        $event->post->afterSave(function (Post $post) use ($event) {
            if (! $post->is_approved) {
                return;
            }

            // A query, not $post->flags()->delete(), which flags' audit logs as a dismissal.
            if (Flag::query()->where('post_id', $post->id)->where('type', 'akismet')->delete()) {
                $post->raise(new Cleared($post, $event->actor));
            }
        });
    }
}
