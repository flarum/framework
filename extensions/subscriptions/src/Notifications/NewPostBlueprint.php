<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Notifications;

use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Flarum\Core\Notifications\Blueprint;
use Flarum\Core\Notifications\MailableBlueprint;

class NewPostBlueprint implements Blueprint, MailableBlueprint
{
    public $post;

    public function __construct(Post $post)
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

    public function getEmailView()
    {
        return ['text' => 'subscriptions::emails.newPost'];
    }

    public function getEmailSubject()
    {
        return '[New Post] '.$this->post->discussion->title;
    }

    public static function getType()
    {
        return 'newPost';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Discussions\Discussion';
    }
}
