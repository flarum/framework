<?php namespace Flarum\Mentions;

use Flarum\Core\Models\Post;
use Flarum\Core\Activity\ActivityAbstract;

class UserMentionedActivity extends ActivityAbstract
{
    public $post;

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
        return 'userMentioned';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Post';
    }
}
