<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\Flags\Event\Deleting;
use Flarum\Flags\Event\FlagsWillBeDeleted;
use Flarum\Flags\Flag;
use Flarum\Post\PostRepository;
use Illuminate\Events\Dispatcher;

class DeleteFlagsHandler
{
    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param PostRepository $posts
     * @param Dispatcher $events
     */
    public function __construct(PostRepository $posts, Dispatcher $events)
    {
        $this->posts = $posts;
        $this->events = $events;
    }

    /**
     * @param DeleteFlags $command
     * @return Flag
     */
    public function handle(DeleteFlags $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $actor->assertCan('viewFlags', $post->discussion);

        // remove beta 17
        $this->events->dispatch(new FlagsWillBeDeleted($post, $actor, $command->data));

        foreach ($post->flags as $flag) {
            $this->events->dispatch(new Deleting($flag, $actor, $command->data));
        }

        $post->flags()->delete();

        return $post;
    }
}
