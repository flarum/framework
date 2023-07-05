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
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Flarum\Post\PostValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class EditPostHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected PostRepository $posts,
        protected PostValidator $validator
    ) {
    }

    public function handle(EditPost $command): Post|CommentPost
    {
        $actor = $command->actor;
        $data = $command->data;

        $post = $this->posts->findOrFail($command->postId, $actor);

        if ($post instanceof CommentPost) {
            $attributes = Arr::get($data, 'attributes', []);

            if (isset($attributes['content'])) {
                $actor->assertCan('edit', $post);

                $post->revise($attributes['content'], $actor);
            }

            if (isset($attributes['isHidden'])) {
                $actor->assertCan('hide', $post);

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
