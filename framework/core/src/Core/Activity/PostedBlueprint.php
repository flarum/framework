<?php namespace Flarum\Core\Activity;

use Flarum\Core\Posts\Post;

/**
 * An activity blueprint for the 'posted' activity type, which represents a user
 * posting in a discussion.
 */
class PostedBlueprint implements Blueprint
{
    /**
     * The user who joined the forum.
     *
     * @var Post
     */
    protected $post;

    /**
     * Create a new 'posted' activity blueprint.
     *
     * @param Post $post The post that was made.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->post;
    }

    /**
     * {@inheritdoc}
     */
    public function getTime()
    {
        return $this->post->time;
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'posted';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return 'Flarum\Core\Posts\Post';
    }
}
