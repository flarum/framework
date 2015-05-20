<?php namespace Flarum\Mentions;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\NotificationAbstract;

class UserMentionedNotification extends NotificationAbstract
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

    public function getSender()
    {
        return $this->post->user;
    }

    public function getEmailView()
    {
        return ['text' => 'mentions::emails.userMentioned'];
    }

    public function getEmailSubject()
    {
        return "{$this->post->user->username} mentioned you in {$this->post->discussion->title}";
    }

    public static function getType()
    {
        return 'userMentioned';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Post';
    }

    public static function isEmailable()
    {
        return true;
    }
}
