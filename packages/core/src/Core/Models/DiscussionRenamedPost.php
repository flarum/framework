<?php namespace Flarum\Core\Models;

class DiscussionRenamedPost extends ActivityPost
{
    /**
     * Merge the post into another post of the same type.
     *
     * @param \Flarum\Core\Models\DiscussionRenamedPost $previous
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
     * @param  int  $discussionId
     * @param  int  $userId
     * @param  string  $oldTitle
     * @param  string  $newTitle
     * @return static
     */
    public static function reply($discussionId, $userId, $oldTitle, $newTitle)
    {
        $post = new static;

        $post->content       = static::buildContent($oldTitle, $newTitle);
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;
        $post->type          = 'discussionRenamed';

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param boolean $oldTitle The old title of the discussion.
     * @param boolean $newTitle The new title of the discussion.
     * @return array
     */
    public static function buildContent($oldTitle, $newTitle)
    {
        return [$oldTitle, $newTitle];
    }
}
