<?php namespace Flarum\Core\Activity;

use Flarum\Core\Models\Post;

class PostedActivity extends ActivityAbstract
{
    protected $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function getSubject()
    {
        return $this->post;
    }

    public function getTime()
    {
        return $this->post->time;
    }

    public static function getType()
    {
        return 'posted';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Post';
    }
}
