<?php namespace Flarum\Categories;

use Flarum\Core\Models\Post;

class DiscussionMovedPost extends Post
{
    /**
     * Create a new instance in reply to a discussion.
     *
     * @param  int  $discussionId
     * @param  int  $userId
     * @param  string  $oldTitle
     * @param  string  $newTitle
     * @return static
     */
    public static function reply($discussionId, $userId, $oldCategoryId, $newCategoryId)
    {
        $post = new static;

        $post->content       = [$oldCategoryId, $newCategoryId];
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;
        $post->type          = 'discussionMoved';

        return $post;
    }

    /**
     * Unserialize the content attribute.
     *
     * @param  string  $value
     * @return string
     */
    public function getContentAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Serialize the content attribute.
     *
     * @param  string  $value
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value);
    }
}
