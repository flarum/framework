<?php namespace Flarum\Mentions;

use Flarum\Core\Models\Post;
use Flarum\Core\Activity\ActivityAbstract;

class PostMentionedActivity extends ActivityAbstract
{
    public $post;

    public $reply;

    public function __construct(Post $post, Post $reply)
    {
        $this->post = $post;
        $this->reply = $reply;
    }

    public function getSubject()
    {
        return $this->reply;
    }

    public function getTime()
    {
        return $this->reply->time;
    }

    public static function getType()
    {
        return 'postMentioned';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Post';
    }
}
