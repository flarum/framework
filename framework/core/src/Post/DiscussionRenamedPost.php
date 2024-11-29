<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Carbon\Carbon;

/**
 * A post which indicates that a discussion's title was changed.
 *
 * The content is stored as a sequential array containing the old title and the
 * new title.
 */
class DiscussionRenamedPost extends AbstractEventPost implements MergeableInterface
{
    public static string $type = 'discussionRenamed';

    public function saveAfter(?Post $previous = null): static
    {
        // If the previous post is another 'discussion renamed' post, and it's
        // by the same user, then we can merge this post into it. If we find
        // that we've in fact reverted the title, delete it. Otherwise, update
        // its content.
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content[0] == $this->content[1]) {
                $previous->delete();
            } else {
                $previous->content = static::buildContent($previous->content[0], $this->content[1]);

                $previous->save();
            }

            return $previous;
        }

        $this->save();

        return $this;
    }

    public static function reply(int $discussionId, int $userId, string $oldTitle, string $newTitle): static
    {
        $post = new static;

        $post->content = static::buildContent($oldTitle, $newTitle);
        $post->created_at = Carbon::now();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }

    protected static function buildContent(string $oldTitle, string $newTitle): array
    {
        return [$oldTitle, $newTitle];
    }
}
