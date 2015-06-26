<?php namespace Flarum\Subscriptions;

use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;
use Flarum\Core\Notifications\NotificationAbstract;

class NewPostNotification extends NotificationAbstract
{
    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function getSubject()
    {
        return $this->post->discussion;
    }

    public function getSender()
    {
        return $this->post->user;
    }

    public function getData()
    {
        return ['postNumber' => (int) $this->post->number];
    }

    public function getEmailView()
    {
        return ['text' => 'flarum-subscriptions::emails.newPost'];
    }

    public function getEmailSubject()
    {
        return '[New Post] '.$this->post->discussion->title;
    }

    public static function getType()
    {
        return 'newPost';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Discussion';
    }

    public static function isEmailable()
    {
        return true;
    }
}
