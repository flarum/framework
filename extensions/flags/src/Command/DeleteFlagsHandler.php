<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\Flags\Event\Deleting;
use Flarum\Flags\Event\Dismissed;
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

        $flags = $post->flags;

        foreach ($flags as $flag) {
            $this->events->dispatch(new Deleting($flag, $actor, $command->data));
        }

        $flags->each->delete();

        if ($flags->isNotEmpty()) {
            $this->events->dispatch(new Dismissed($post, $flags, $actor, $command->data));
        }

        return $post;
    }
}
