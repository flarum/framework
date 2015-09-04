<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Notifications;

use Flarum\Lock\Posts\DiscussionLockedPost;
use Flarum\Core\Notifications\Blueprint;

class DiscussionLockedBlueprint implements Blueprint
{
    protected $post;

    public function __construct(DiscussionLockedPost $post)
    {
        $this->post = $post;
    }

    public function getSender()
    {
        return $this->post->user;
    }

    public function getSubject()
    {
        return $this->post->discussion;
    }

    public function getData()
    {
        return ['postNumber' => (int) $this->post->number];
    }

    public static function getType()
    {
        return 'discussionLocked';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Discussions\Discussion';
    }
}
