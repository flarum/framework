<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Event;

use Flarum\Post\Post;
use Flarum\User\User;

class Posted
{
    /**
     * @var \Flarum\Post\Post
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Flarum\Post\Post $post
     */
    public function __construct(Post $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
