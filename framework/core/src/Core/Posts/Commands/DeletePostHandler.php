<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Posts\PostRepository;
use Flarum\Events\PostWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeletePostHandler
{
    use DispatchesEvents;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param DeletePost $command
     * @return \Flarum\Core\Posts\Post
     */
    public function handle(DeletePost $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $post->assertCan($actor, 'delete');

        event(new PostWillBeDeleted($post, $actor, $command->data));

        $post->delete();

        $this->dispatchEventsFor($post);

        return $post;
    }
}
