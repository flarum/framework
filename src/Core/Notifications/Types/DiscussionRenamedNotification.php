<?php namespace Flarum\Core\Notifications\Types;

use Flarum\Core\Models\User;
use Flarum\Core\Models\DiscussionRenamedPost;

class DiscussionRenamedNotification extends Notification implements AlertableNotification
{
    public $post;

    public $oldTitle;

    public function __construct(User $recipient, User $sender, DiscussionRenamedPost $post, $oldTitle)
    {
        $this->post = $post;
        $this->oldTitle = $oldTitle;

        parent::__construct($recipient, $sender);
    }

    public function getSubject()
    {
        return $this->post->discussion;
    }

    public function getAlertData()
    {
        return [
            'number'   => $this->post->number,
            'oldTitle' => $this->oldTitle
        ];
    }

    public static function getType()
    {
        return 'discussionRenamed';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Discussion';
    }
}
