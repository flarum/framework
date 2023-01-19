<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Event;

use Flarum\Post\CommentPost;
use Flarum\User\User;

class Posted
{
    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @var User|null
     */
    public $actor;

    /**
     * @param CommentPost $post
     * @param User|null $actor
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
