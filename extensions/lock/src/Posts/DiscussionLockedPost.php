<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Posts;

use Flarum\Core\Posts\Post;
use Flarum\Core\Posts\EventPost;
use Flarum\Core\Posts\MergeablePost;

class DiscussionLockedPost extends EventPost implements MergeablePost
{
    public static $type = 'discussionLocked';

    public function saveAfter(Post $previous)
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

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param integer $discussionId
     * @param integer $userId
     * @param boolean $isLocked
     * @return static
     */
    public static function reply($discussionId, $userId, $isLocked)
    {
        $post = new static;

        $post->content       = static::buildContent($isLocked);
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param boolean $isLocked Whether or not the discussion is stickied.
     * @return array
     */
    public static function buildContent($isLocked)
    {
        return ['locked' => (bool) $isLocked];
    }
}
