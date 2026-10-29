<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Discussion\Discussion;

class UpdateTagMetadataAfterApproval
{
    public function handle(PostWasApproved $event): void
    {
        $discussion = $event->post->discussion;

        // flarum/tags counts a discussion only once its first post is approved;
        // approving a later reply just needs the last-posted metadata refreshed.
        $countable = $event->post->number === 1;

        // flarum/tags skips private (pending-approval) content, so its counters
        // are left stale after approval. The visibility change is applied
        // separately by UpdateDiscussionAfterPostApproval and listener order is
        // not guaranteed, so defer to the discussion's save while still private.
        if ($discussion->is_private) {
            $discussion->afterSave(fn (Discussion $discussion) => $this->refreshTags($discussion, $countable));
        } else {
            $this->refreshTags($discussion, $countable);
        }
    }

    private function refreshTags(Discussion $discussion, bool $countable): void
    {
        if ($discussion->is_private || $discussion->hidden_at !== null) {
            return;
        }

        foreach ($discussion->tags as $tag) {
            if ($countable) {
                $tag->discussion_count++;
            }

            $tag->refreshLastPostedDiscussion()->save();
        }
    }
}
