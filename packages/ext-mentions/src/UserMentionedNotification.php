<?php namespace Flarum\Mentions;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Types\AlertableNotification;
use Flarum\Core\Notifications\Types\EmailableNotification;

class UserMentionedNotification extends Notification implements
    AlertableNotification,
    EmailableNotification
{
    public $sender;

    public $post;

    public function __construct(User $sender, Post $post)
    {
        $this->sender = $sender;
        $this->post = $post;
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
        return null;
    }

    public function getEmailView()
    {
        return ['text' => 'mentions::emails.userMentioned'];
    }

    public function getEmailSubject()
    {
        return "{$this->sender->username} mentioned you in {$this->post->discussion->title}";
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
