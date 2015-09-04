<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Likes\Listeners;

use Flarum\Likes\Events\PostWasLiked;
use Flarum\Likes\Events\PostWasUnliked;
use Flarum\Events\PostWillBeSaved;
use Flarum\Events\PostWasDeleted;
use Flarum\Core\Posts\Post;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;

class PersistData
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'whenPostWillBeSaved']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
    }

    public function whenPostWillBeSaved(PostWillBeSaved $event)
    {
        $post = $event->post;
        $data = $event->data;

        if ($post->exists && isset($data['attributes']['isLiked'])) {
            $actor = $event->actor;
            $liked = (bool) $data['attributes']['isLiked'];

            if (! $post->can($actor, 'like')) {
                throw new PermissionDeniedException;
            }

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

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $event->post->likes()->detach();
    }
}
