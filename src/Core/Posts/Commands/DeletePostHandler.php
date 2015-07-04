<?php namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Posts\PostRepositoryInterface;
use Flarum\Core\Posts\Events\PostWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeletePostHandler
{
    use DispatchesEvents;

    /**
     * @var PostRepositoryInterface
     */
    protected $posts;

    /**
     * @param PostRepositoryInterface $posts
     */
    public function __construct(PostRepositoryInterface $posts)
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
