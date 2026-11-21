<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Flags\Event\Cleared;
use Flarum\Flags\Flag;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

class ApproveContent
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Saving::class, $this->approvePost(...));
    }

    /**
     * @throws PermissionDeniedException
     */
    public function approvePost(Saving $event): void
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
         *   We approve it silently if the action is hiding.
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

            $post->afterSave(fn (Post $post) => $this->removeApprovalFlag($post, $event->actor));
        }
    }

    protected function removeApprovalFlag(Post $post, User $actor): void
    {
        // Another Saving listener (Akismet) may have held the post again.
        if (! $post->is_approved) {
            return;
        }

        // A query, not $post->flags()->delete(): flags' audit integration logs that as a
        // moderator dismissing the post's flags.
        // Raised, not dispatched: it then follows PostWasApproved, so a failing broadcast
        // can't stop the discussion's approval.
        if (Flag::query()->where('post_id', $post->id)->where('type', 'approval')->delete()) {
            $post->raise(new Cleared($post, $actor));
        }
    }
}
