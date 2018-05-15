<?php

namespace Flarum\Tests\Test\Concerns;

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

        if (!$this->discussion->startPost) {
            $this->discussion->setStartPost($post);
            $this->discussion->setLastPost($post);

            $this->discussion->save();

            event(new Posted($post, $actor));
        }

        return $post;
    }
}
