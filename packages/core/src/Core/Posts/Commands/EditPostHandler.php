<?php namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Posts\PostRepositoryInterface;
use Flarum\Core\Posts\Events\PostWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Posts\CommentPost;

class EditPostHandler
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
     * @param EditPost $command
     * @return \Flarum\Core\Posts\Post
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(EditPost $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $post = $this->posts->findOrFail($command->postId, $actor);

        if ($post instanceof CommentPost) {
            $attributes = array_get($data, 'attributes', []);

            if (isset($attributes['content'])) {
                $post->assertCan($actor, 'edit');

                $post->revise($attributes['content'], $actor);
            }

            if (isset($attributes['isHidden'])) {
                $post->assertCan($actor, 'edit');

                if ($attributes['isHidden']) {
                    $post->hide($actor);
                } else {
                    $post->restore();
                }
            }
        }

        event(new PostWillBeSaved($post, $actor, $data));

        $post->save();

        $this->dispatchEventsFor($post);

        return $post;
    }
}
