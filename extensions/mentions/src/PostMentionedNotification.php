<?php namespace Flarum\Mentions;

use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\NotificationAbstract;

class PostMentionedNotification extends NotificationAbstract
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
        return $this->post;
    }

    public function getSender()
    {
        return $this->reply->user;
    }

    public function getData()
    {
        return ['replyNumber' => $this->reply->number];
    }

    public function getEmailView()
    {
        return ['text' => 'mentions::emails.postMentioned'];
    }

    public function getEmailSubject()
    {
        return "{$this->reply->user->username} replied to your post in {$this->post->discussion->title}";
    }

    public static function getType()
    {
        return 'postMentioned';
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
