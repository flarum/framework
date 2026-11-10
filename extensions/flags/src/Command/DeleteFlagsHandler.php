<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\Flags\Event\Cleared;
use Flarum\Flags\Event\Deleting;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Illuminate\Events\Dispatcher;

class DeleteFlagsHandler
{
    public function __construct(
        protected PostRepository $posts,
        protected Dispatcher $events
    ) {
    }

    public function handle(DeleteFlags $command): Post
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $actor->assertCan('viewFlags', $post->discussion);

        foreach ($post->flags as $flag) {
            $this->events->dispatch(new Deleting($flag, $actor, $command->data));
        }

        if ($post->flags()->delete()) {
            $this->events->dispatch(new Cleared($post, $actor));
        }

        return $post;
    }
}
