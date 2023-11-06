<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Approval\Event\PostWasUnapproved;
use Flarum\Approval\RefreshesDiscussionTrait;

class UpdateDiscussionAfterPostUnapproval
{
    use RefreshesDiscussionTrait;

    public function handle(PostWasUnapproved $event)
    {
        $this->refreshAndSaveDiscussion($event->post,function ($post, $discussion, $user) {
            if ($post->number === 1) {
                $discussion->is_approved = true;
                $discussion->hide();

                $discussion->afterSave(function () use ($user) {
                    $user->refreshDiscussionCount();
                });
            }
        });
    }
}
