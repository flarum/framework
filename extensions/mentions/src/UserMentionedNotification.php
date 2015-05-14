<?php namespace Flarum\Mentions;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Types\AlertableNotification;

class UserMentionedNotification extends Notification implements AlertableNotification
{
    protected $sender;

    protected $post;

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

    public static function getType()
    {
        return 'userMentioned';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Post';
    }
}
