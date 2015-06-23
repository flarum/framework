<?php namespace Flarum\Likes\Handlers;

use Flarum\Likes\Events\PostWasLiked;
use Flarum\Likes\Events\PostWasUnliked;
use Flarum\Core\Events\PostWillBeSaved;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Models\Post;
use Flarum\Core\Exceptions\PermissionDeniedException;

class LikedSaver
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\PostWillBeSaved', __CLASS__.'@whenPostWillBeSaved');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
    }

    public function whenPostWillBeSaved(PostWillBeSaved $event)
    {
        $post = $event->post;
        $data = $event->command->data;

        if ($post->exists && isset($data['isLiked'])) {
            $user = $event->command->user;
            $liked = (bool) $data['isLiked'];

            if (! $post->can($user, 'like')) {
                throw new PermissionDeniedException;
            }

            if ($liked) {
                $post->likes()->attach($user->id);

                $post->raise(new PostWasLiked($post, $user));
            } else {
                $post->likes()->detach($user->id);

                $post->raise(new PostWasUnliked($post, $user));
            }
        }
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $event->post->likes()->detach();
    }
}
