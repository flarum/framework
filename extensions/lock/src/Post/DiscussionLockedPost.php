<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Lock\Post;

use Carbon\Carbon;
use Flarum\Post\AbstractEventPost;
use Flarum\Post\MergeableInterface;
use Flarum\Post\Post;

class DiscussionLockedPost extends AbstractEventPost implements MergeableInterface
{
    public static string $type = 'discussionLocked';

    public function saveAfter(Post $previous = null): static
    {
        // If the previous post is another 'discussion locked' post, and it's
        // by the same user, then we can merge this post into it. If we find
        // that we've in fact reverted the locked status, delete it. Otherwise,
        // update its content.
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content['locked'] != $this->content['locked']) {
                $previous->delete();
            } else {
                $previous->content = $this->content;

                $previous->save();
            }

            return $previous;
        }

        $this->save();

        return $this;
    }

    public static function reply(int $discussionId, int $userId, bool $isLocked): static
    {
        $post = new static;

        $post->content = static::buildContent($isLocked);
        $post->created_at = Carbon::now();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }

    public static function buildContent(bool $isLocked): array
    {
        return ['locked' => $isLocked];
    }
}
