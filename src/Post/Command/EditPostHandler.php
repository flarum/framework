<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving;
use Flarum\Post\PostRepository;
use Flarum\Post\PostValidator;
use Flarum\User\AssertPermissionTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class EditPostHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var \Flarum\Post\PostRepository
     */
    protected $posts;

    /**
     * @var \Flarum\Post\PostValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param PostRepository $posts
     * @param \Flarum\Post\PostValidator $validator
     */
    public function __construct(Dispatcher $events, PostRepository $posts, PostValidator $validator)
    {
        $this->events = $events;
        $this->posts = $posts;
        $this->validator = $validator;
    }

    /**
     * @param EditPost $command
     * @return \Flarum\Post\Post
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(EditPost $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $post = $this->posts->findOrFail($command->postId, $actor);

        if ($post instanceof CommentPost) {
            $attributes = Arr::get($data, 'attributes', []);

            if (isset($attributes['content'])) {
                $this->assertCan($actor, 'edit', $post);

                $post->revise($attributes['content'], $actor);
            }

            if (isset($attributes['isHidden'])) {
                $this->assertCan($actor, 'hide', $post);

                if ($attributes['isHidden']) {
                    $post->hide($actor);
                } else {
                    $post->restore();
                }
            }
        }

        $this->events->dispatch(
            new Saving($post, $actor, $data)
        );

        $this->validator->assertValid($post->getDirty());

        $post->save();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
