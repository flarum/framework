<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Post\Event\Saving;
use Illuminate\Contracts\Events\Dispatcher;

class ApproveContent
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Saving::class, [$this, 'approvePost']);
        $events->listen(PostWasApproved::class, [$this, 'approveDiscussion']);
    }

    /**
     * @param Saving $event
     */
    public function approvePost(Saving $event)
    {
        $attributes = $event->data['attributes'];
        $post = $event->post;

        if (isset($attributes['isApproved'])) {
            $event->actor->assertCan('approve', $post);

            $isApproved = (bool) $attributes['isApproved'];
        } elseif (! empty($attributes['isHidden']) && $event->actor->can('approve', $post)) {
            $isApproved = true;
        }

        if (! empty($isApproved)) {
            $post->is_approved = true;

            $post->raise(new PostWasApproved($post, $event->actor));
        }
    }

    /**
     * @param PostWasApproved $event
     */
    public function approveDiscussion(PostWasApproved $event)
    {
        $post = $event->post;
        $discussion = $post->discussion;
        $user = $discussion->user;

        $discussion->refreshCommentCount();
        $discussion->refreshLastPost();

        if ($post->number == 1) {
            $discussion->is_approved = true;

            $discussion->afterSave(function () use ($user) {
                $user->refreshDiscussionCount();
            });
        }

        $discussion->save();

        $user->refreshCommentCount();
        $user->save();
    }
}
