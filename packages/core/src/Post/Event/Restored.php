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

class Restored
{
    /**
     * @var \Flarum\Post\CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Flarum\Post\CommentPost $post
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
