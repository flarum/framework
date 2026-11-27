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

        if (! isset($attributes['isApproved']) && ! isset($attributes['isHidden'])) {
            return;
        }

        // Hiding a pending post approves it silently, and leaves a pending discussion pending,
        // so restoring or approving the post must still approve it.
        if ($post->is_approved && ! $post->is_approved_silently && ($post->number !== 1 || $post->discussion->is_approved)) {
            return;
        }

        $approvingSilently = false;
        $restoring = isset($attributes['isHidden']) && ! $attributes['isHidden'];

        if (isset($attributes['isApproved'])) {
            $event->actor->assertCan('approve', $post);

            $isApproved = (bool) $attributes['isApproved'];
        } elseif (isset($attributes['isHidden']) && $event->actor->can('approve', $post)) {
            $isApproved = true;
            // Hiding a pending post rejects it: it is approved, but not announced.
            $approvingSilently = $attributes['isHidden'];
        } elseif ($post->is_approved && ! $attributes['isHidden']) {
            // A restore by someone who can't approve makes the post public without announcing it.
            // Drop the deferred approval so a later moderation action doesn't announce it as new.
            $post->is_approved_silently = false;
        }

        if (! empty($isApproved)) {
            $post->is_approved = true;
            $post->is_approved_silently = $approvingSilently;

            $post->afterSave(function (Post $post) use ($event, $approvingSilently, $restoring) {
                // Another Saving listener (Akismet) may have held the post again.
                if (! $post->is_approved) {
                    return;
                }

                // Another Saving listener (Akismet) may have hidden the restored post again.
                // Keep the approval deferred until it is actually restored.
                if ($restoring && $post->hidden_at) {
                    $post->is_approved_silently = true;
                    $post->newQuery()->whereKey($post->getKey())->update(['is_approved_silently' => true]);

                    return;
                }

                if (! $approvingSilently) {
                    $post->raise(new PostWasApproved($post, $event->actor));
                }

                // After PostWasApproved, so a failing Cleared broadcast can't stop the
                // discussion's approval.
                $this->removeApprovalFlag($post, $event->actor);
            });
        }
    }

    protected function removeApprovalFlag(Post $post, User $actor): void
    {
        // A query, not $post->flags()->delete(), which flags' audit logs as a moderator
        // dismissing flags.
        if (Flag::query()->where('post_id', $post->id)->where('type', 'approval')->delete()) {
            $post->raise(new Cleared($post, $actor));
        }
    }
}
