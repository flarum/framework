<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Listener;

use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Saving;
use Illuminate\Contracts\Events\Dispatcher;

class SaveLikesToDatabase
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Saving::class, $this->whenPostIsSaving(...));
        $events->listen(Deleted::class, $this->whenPostIsDeleted(...));
    }

    public function whenPostIsSaving(Saving $event): void
    {
        $post = $event->post;
        $data = $event->data;

        if ($post->exists && isset($data['attributes']['isLiked'])) {
            $actor = $event->actor;
            $liked = (bool) $data['attributes']['isLiked'];

            $actor->assertCan('like', $post);

            $currentlyLiked = $post->likes()->where('user_id', $actor->id)->exists();

            if ($liked && ! $currentlyLiked) {
                $post->likes()->attach($actor->id);

                $post->raise(new PostWasLiked($post, $actor));
            } elseif ($currentlyLiked) {
                $post->likes()->detach($actor->id);

                $post->raise(new PostWasUnliked($post, $actor));
            }
        }
    }

    public function whenPostIsDeleted(Deleted $event): void
    {
        $event->post->likes()->detach();
    }
}
