<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Likes\Notifications;

use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Flarum\Core\Notifications\Blueprint;

class PostLikedBlueprint implements Blueprint
{
    public $post;

    public $user;

    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    public function getSubject()
    {
        return $this->post;
    }

    public function getSender()
    {
        return $this->user;
    }

    public function getData()
    {
        return null;
    }

    public static function getType()
    {
        return 'postLiked';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Posts\Post';
    }
}
