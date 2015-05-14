<?php namespace Flarum\Categories;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\Notification as NotificationModel;
use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Types\AlertableNotification;

class DiscussionMovedNotification extends Notification implements AlertableNotification
{
    protected $discussion;

    protected $sender;

    protected $post;

    public function __construct(Discussion $discussion, User $sender, DiscussionMovedPost $post = null)
    {
        $this->discussion = $discussion;
        $this->sender = $sender;
        $this->post = $post;
    }

    public function getSubject()
    {
        return $this->discussion;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function getAlertData()
    {
        return ['postNumber' => $this->post->number];
    }

    public static function getType()
    {
        return 'discussionMoved';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Discussion';
    }
}
