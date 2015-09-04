<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Notifications;

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
