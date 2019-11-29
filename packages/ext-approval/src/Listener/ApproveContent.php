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
use Flarum\User\AssertPermissionTrait;
use Illuminate\Contracts\Events\Dispatcher;

class ApproveContent
{
    use AssertPermissionTrait;

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
            $this->assertCan($event->actor, 'approve', $post);

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

        $discussion->refreshCommentCount();
        $discussion->refreshLastPost();

        if ($post->number == 1) {
            $discussion->is_approved = true;
        }

        $discussion->save();
    }
}
