<?php namespace Flarum\Likes\Notifications;

use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Flarum\Core\Notifications\Blueprint;

class PostLikedBlueprint implements Blueprint
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

    public function getData()
    {
        return null;
    }

    public static function getType()
    {
        return 'postLiked';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Posts\Post';
    }
}
