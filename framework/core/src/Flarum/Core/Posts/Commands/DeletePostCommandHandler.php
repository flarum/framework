<?php namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Posts\PostRepository;
use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

class DeletePostCommandHandler implements CommandHandler
{
    use DispatchableTrait;

    protected $posts;

    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    public function handle($command)
    {
        $user = $command->user;
        $post = $this->posts->findOrFail($command->postId, $user);

        $post->assertCan($user, 'delete');

        Event::fire('Flarum.Core.Posts.Commands.DeletePost.PostWillBeDeleted', [$post, $command]);

        $this->posts->delete($post);
        $this->dispatchEventsFor($post);

        return $post;
    }
}
