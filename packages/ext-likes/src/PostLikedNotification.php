<?php namespace Flarum\Likes;

use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;
use Flarum\Core\Notifications\NotificationAbstract;

class PostLikedNotification extends NotificationAbstract
{
    public $post;

    public $user;

    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    public function getSubject()
    {
        return $this->post;
    }

    public function getSender()
    {
        return $this->user;
    }

    public static function getType()
    {
        return 'postLiked';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Post';
    }
}
