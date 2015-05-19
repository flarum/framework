<?php namespace Flarum\Mentions;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Types\AlertableNotification;
use Flarum\Core\Notifications\Types\EmailableNotification;

class PostMentionedNotification extends Notification implements
    AlertableNotification,
    EmailableNotification
{
    public $post;

    public $sender;

    public $reply;

    public function __construct(Post $post, User $sender, Post $reply)
    {
        $this->post = $post;
        $this->sender = $sender;
        $this->reply = $reply;
    }

    public function getSubject()
    {
        return $this->post;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function getAlertData()
    {
        return ['replyNumber' => $this->reply->number];
    }

    public function getEmailView()
    {
        return ['text' => 'mentions::emails.postMentioned'];
    }

    public function getEmailSubject()
    {
        return "{$this->sender->username} replied to your post in {$this->post->discussion->title}";
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
