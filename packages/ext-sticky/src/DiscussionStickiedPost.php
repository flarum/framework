<?php namespace Flarum\Sticky;

use Flarum\Core\Models\Model;
use Flarum\Core\Models\EventPost;

class DiscussionStickiedPost extends EventPost
{
    /**
     * The type of post this is, to be stored in the posts table.
     *
     * @var string
     */
    public static $type = 'discussionStickied';

    /**
     * Merge the post into another post of the same type.
     *
     * @param \Flarum\Core\Models\DiscussionRenamedPost $previous
     * @return \Flarum\Core\Models\Model|null The final model, or null if the
     *     previous post was deleted.
     */
    protected function mergeInto(Model $previous)
    {
        if ($this->user_id === $previous->user_id) {
            if ($previous->content['sticky'] != $this->content['sticky']) {
                return;
            }

            $previous->content = $this->content;
            return $previous;
        }

        return $this;
    }

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param integer $discussionId
     * @param integer $userId
     * @param boolean $isSticky
     * @return static
     */
    public static function reply($discussionId, $userId, $isSticky)
    {
        $post = new static;

        $post->content       = static::buildContent($isSticky);
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param boolean $isSticky Whether or not the discussion is stickied.
     * @return array
     */
    public static function buildContent($isSticky)
    {
        return ['sticky' => (bool) $isSticky];
    }
}
