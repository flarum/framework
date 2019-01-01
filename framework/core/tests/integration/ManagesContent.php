<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;

trait ManagesContent
{
    use RetrievesAuthorizedUsers;

    protected function addPostByNormalUser(): CommentPost
    {
        $actor = $this->getNormalUser();

        $post = CommentPost::reply(
            $this->discussion->id,
            'a normal reply - too-obscure',
            $actor->id,
            '127.0.0.1'
        );

        $post->save();

        if (! $this->discussion->firstPost) {
            $this->discussion->setFirstPost($post);
            $this->discussion->setLastPost($post);

            $this->discussion->save();

            event(new Posted($post, $actor));
        }

        return $post;
    }
}
