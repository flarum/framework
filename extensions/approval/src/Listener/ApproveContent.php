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
use Flarum\User\Exception\PermissionDeniedException;
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

    /**
     * @throws  PermissionDeniedException
     */
    public function approvePost(Saving $event)
    {
        $attributes = $event->data['attributes'];
        $post = $event->post;

        // Nothing to do if it is already approved.
        if ($post->is_approved) {
            return;
        }

        /*
         * We approve a post in one of two cases:
         * - The post was unapproved and the allowed action is approving it. We trigger an event.
         * - The post was unapproved and the allowed actor is hiding or un-hiding it.
         *   We approve it silently if the action is unhiding.
         */
        $approvingSilently = false;

        if (isset($attributes['isApproved'])) {
            $event->actor->assertCan('approve', $post);

            $isApproved = (bool) $attributes['isApproved'];
        } elseif (isset($attributes['isHidden']) && $event->actor->can('approve', $post)) {
            $isApproved = true;
            $approvingSilently = $attributes['isHidden'];
        }

        if (! empty($isApproved)) {
            $post->is_approved = true;

            if (! $approvingSilently) {
                $post->raise(new PostWasApproved($post, $event->actor));
            }
        }
    }
}
