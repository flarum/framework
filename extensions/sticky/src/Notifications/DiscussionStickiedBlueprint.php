<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Sticky\Notifications;

use Flarum\Sticky\Posts\DiscussionStickiedPost;
use Flarum\Core\Notifications\Blueprint;

class DiscussionStickiedBlueprint implements Blueprint
{
    protected $post;

    public function __construct(DiscussionStickiedPost $post)
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
        return 'discussionStickied';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Discussions\Discussion';
    }
}
