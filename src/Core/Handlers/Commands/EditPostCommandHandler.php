<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\PostRepositoryInterface as PostRepository;
use Flarum\Core\Events\PostWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class EditPostCommandHandler
{
    use DispatchesEvents;

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

        if ($command->isHidden === true) {
            $post->hide($user);
        } elseif ($command->isHidden === false) {
            $post->restore($user);
        }

        event(new PostWillBeSaved($post, $command));

        $post->save();
        $this->dispatchEventsFor($post);

        return $post;
    }
}
