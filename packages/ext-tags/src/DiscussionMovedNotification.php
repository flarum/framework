<?php namespace Flarum\Categories;

use Flarum\Core\Models\User;
use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Types\AlertableNotification;

class DiscussionMovedNotification extends Notification implements AlertableNotification
{
    public $post;

    public function __construct(User $recipient, User $sender, DiscussionMovedPost $post)
    {
        $this->post = $post;

        parent::__construct($recipient, $sender);
    }

    public function getSubject()
    {
        return $this->post->discussion;
    }

    public function getAlertData()
    {
        return [
            'postNumber' => $this->post->number
        ];
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
