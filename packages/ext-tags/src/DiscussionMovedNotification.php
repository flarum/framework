<?php namespace Flarum\Categories;

use Flarum\Core\Notifications\NotificationAbstract;

class DiscussionMovedNotification extends NotificationAbstract
{
    protected $post;

    public function __construct(DiscussionMovedPost $post)
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

    public static function getType()
    {
        return 'discussionMoved';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\Discussion';
    }
}
