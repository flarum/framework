<?php namespace Flarum\Mentions\Notifications;

use Flarum\Core\Users\User;
use Flarum\Core\Posts\Post;
use Flarum\Core\Notifications\Blueprint;
use Flarum\Core\Notifications\MailableBlueprint;

class UserMentionedBlueprint implements Blueprint, MailableBlueprint
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

    public function getData()
    {
        return null;
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
        return Post::class;
    }
}
