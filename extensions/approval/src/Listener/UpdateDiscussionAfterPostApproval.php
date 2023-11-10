<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Approval\Event\PostWasApproved;

class UpdateDiscussionAfterPostApproval
{
    public function handle(PostWasApproved $event)
    {
        $post = $event->post;
        $discussion = $post->discussion;
        $user = $discussion->user;

        $discussion->refreshCommentCount();
        $discussion->refreshLastPost();

        if ($post->number === 1) {
            $discussion->is_approved = true;

            $discussion->afterSave(function () use ($user) {
                $user->refreshDiscussionCount();
            });
        }

        $discussion->save();

        if ($discussion->user) {
            $user->refreshCommentCount();
            $user->save();
        }

        if ($post->user) {
            $post->user->refreshCommentCount();
            $post->user->save();
        }
    }
}
