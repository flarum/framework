<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Post\Event\Deleting;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeletePostHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected PostRepository $posts
    ) {
    }

    public function handle(DeletePost $command): Post
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $actor->assertCan('delete', $post);

        $this->events->dispatch(
            new Deleting($post, $actor, $command->data)
        );

        $post->delete();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
