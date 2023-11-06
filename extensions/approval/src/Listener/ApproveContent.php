<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Approval\Event\PostWasUnapproved;
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
    }

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
            // Set the post's approval status to true to clear any pending approval status, even if the post is hidden.
            $post->is_approved = true;

            if(! $post->hidden_at) {
                $post->raise(new PostWasApproved($post, $event->actor));
            } else {
                $post->raise(new PostWasUnapproved($post, $event->actor));
            }
        }
    }
}
