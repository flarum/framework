<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Posts\CommentPost;

class PostWasHidden
{
    /**
     * The post that was hidden.
     *
     * @var CommentPost
     */
    public $post;

    /**
     * @param CommentPost $post
     */
    public function __construct(CommentPost $post)
    {
        $this->post = $post;
    }
}
