<?php namespace Flarum\Mentions;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Types\AlertableNotification;

class PostMentionedNotification extends Notification implements AlertableNotification
{
    protected $post;

    protected $sender;

    protected $reply;

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

    public static function getType()
    {
        return 'postMentioned';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Post';
    }
}
