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
use Flarum\Events\PostWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Posts\CommentPost;

class EditPostHandler
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
