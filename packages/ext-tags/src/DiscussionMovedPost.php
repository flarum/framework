<?php namespace Flarum\Categories;

use Flarum\Core\Models\Model;
use Flarum\Core\Models\ActivityPost;

class DiscussionMovedPost extends ActivityPost
{
    /**
     * The type of post this is, to be stored in the posts table.
     *
     * @var string
     */
    public static $type = 'discussionMoved';

    /**
     * Merge the post into another post of the same type.
     *
     * @param \Flarum\Core\Models\Model $previous
     * @return boolean true if the post was merged, false if it was deleted.
     */
    protected function mergeInto(Model $previous)
    {
        if ($previous->content[0] == $this->content[1]) {
            return false;
        }

        $previous->content = static::buildContent($previous->content[0], $this->content[1]);
        return true;
    }

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param integer $discussionId
     * @param integer $userId
     * @param integer $oldCategoryId
     * @param integer $newCategoryId
     * @return static
     */
    public static function reply($discussionId, $userId, $oldCategoryId, $newCategoryId)
    {
        $post = new static;

        $post->content       = static::buildContent($oldCategoryId, $newCategoryId);
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param boolean $oldCategoryId The old category ID.
     * @param boolean $newCategoryId The new category ID.
     * @return array
     */
    public static function buildContent($oldCategoryId, $newCategoryId)
    {
        return [$oldCategoryId, $newCategoryId];
    }
}
