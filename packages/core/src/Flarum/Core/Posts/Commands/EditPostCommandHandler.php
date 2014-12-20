<?php namespace Flarum\Core\Posts\Commands;

use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

use Flarum\Core\Posts\PostRepository;

class EditPostCommandHandler implements CommandHandler
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

        $post->assertCan($user, 'edit');

        if (isset($command->content)) {
            $post->revise($command->content, $user);
        }

        if ($command->hidden === true) {
            $post->hide($user);
        } elseif ($command->hidden === false) {
            $post->restore($user);
        }

        Event::fire('Flarum.Core.Posts.Commands.EditPost.PostWillBeSaved', [$post, $command]);

        $this->posts->save($post);
        $this->dispatchEventsFor($post);

        return $post;
    }
}
