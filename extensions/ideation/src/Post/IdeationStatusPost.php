<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Post;

use Carbon\Carbon;
use Flarum\Post\AbstractEventPost;
use Flarum\Post\MergeableInterface;
use Flarum\Post\Post;

/**
 * "Ernest moved this to Planned", in the idea's own discussion, so the
 * history of an idea is read where its conversation is.
 *
 * Content: [old status, new status, merged-into discussion id], plus the
 * GitHub item that caused it when the move was automatic.
 *
 * Only the id of the idea it was merged into, never its title: everyone
 * who can read this discussion reads this post, and the other idea may sit
 * somewhere they cannot see. The forum loads the title through the API,
 * which checks.
 * Like core's rename post, two moves in a row by the same person become one,
 * and moving it straight back removes the post.
 */
class IdeationStatusPost extends AbstractEventPost implements MergeableInterface
{
    public static string $type = 'ideationStatus';

    public function saveAfter(?Post $previous = null): static
    {
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content[0] === $this->content[1]) {
                $previous->delete();
            } else {
                $previous->content = [$previous->content[0], ...array_slice($this->content, 1)];
                $previous->save();
            }

            return $previous;
        }

        $this->save();

        return $this;
    }

    public static function reply(int $discussionId, ?int $userId, ?string $old, ?string $new, ?int $mergedIntoId = null, ?string $via = null): static
    {
        $post = new static;

        $post->content = $via === null ? [$old, $new, $mergedIntoId] : [$old, $new, $mergedIntoId, $via];
        $post->created_at = Carbon::now();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }
}
