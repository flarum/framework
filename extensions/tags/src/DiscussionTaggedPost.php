<?php namespace Flarum\Tags;

use Flarum\Core\Models\Model;
use Flarum\Core\Models\EventPost;

class DiscussionTaggedPost extends EventPost
{
    /**
     * The type of post this is, to be stored in the posts table.
     *
     * @var string
     */
    public static $type = 'discussionTagged';

    /**
     * Merge the post into another post of the same type.
     *
     * @param \Flarum\Core\Models\Model $previous
     * @return \Flarum\Core\Models\Model|null The final model, or null if the
     *     previous post was deleted.
     */
    protected function mergeInto(Model $previous)
    {
        if ($this->user_id === $previous->user_id) {
            if ($previous->content[0] == $this->content[1]) {
                return;
            }

            $previous->content = static::buildContent($previous->content[0], $this->content[1]);
            $previous->time = $this->time;

            return $previous;
        }

        return $this;
    }

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param integer $discussionId
     * @param integer $userId
     * @param array $oldTagIds
     * @param array $newTagIds
     * @return static
     */
    public static function reply($discussionId, $userId, array $oldTagIds, array $newTagIds)
    {
        $post = new static;

        $post->content       = static::buildContent($oldTagIds, $newTagIds);
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param array $oldTagIds
     * @param array $newTagIds
     * @return array
     */
    public static function buildContent(array $oldTagIds, array $newTagIds)
    {
        return [$oldTagIds, $newTagIds];
    }
}
