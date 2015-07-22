<?php namespace Flarum\Likes\Listeners;

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
        $events->listen(PostWillBeSaved::class, __CLASS__.'@whenPostWillBeSaved');
        $events->listen(PostWasDeleted::class, __CLASS__.'@whenPostWasDeleted');
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

            if ($liked) {
                $post->likes()->attach($actor->id);

                $post->raise(new PostWasLiked($post, $actor));
            } else {
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
