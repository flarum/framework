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
use Flarum\Post\Event\Deleting;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Keeps users.like_count, the number of posts a user has liked, in step with
 * the Likes tab on their profile. Like core's comment_count, it's a full
 * recount each time, and private posts aren't counted.
 */
class UpdateUserLikeCount
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PostWasLiked::class, $this->whenPostWasLiked(...));
        $events->listen(PostWasUnliked::class, $this->whenPostWasUnliked(...));
        $events->listen(Deleting::class, $this->whenPostIsBeingDeleted(...));
        $events->listen(Deleted::class, $this->whenPostWasDeleted(...));
    }

    public function whenPostWasLiked(PostWasLiked $event): void
    {
        $this->refresh($event->user);
    }

    public function whenPostWasUnliked(PostWasUnliked $event): void
    {
        $this->refresh($event->user);
    }

    public function whenPostIsBeingDeleted(Deleting $event): void
    {
        // The likes go with the post (post_likes cascades on delete), so note
        // who liked it while the rows are still there.
        $event->post->loadMissing('likes');
    }

    public function whenPostWasDeleted(Deleted $event): void
    {
        $likers = $event->post->likes;

        $event->post->likes()->detach();

        foreach ($likers as $user) {
            $this->refresh($user);
        }
    }

    protected function refresh(User $user): void
    {
        if (! $user->exists) {
            return;
        }

        $user->like_count = Post::query()
            ->whereIn('id', function ($query) use ($user) {
                $query->select('post_id')
                    ->from('post_likes')
                    ->where('user_id', $user->id);
            })
            ->where('is_private', false)
            ->count();

        $user->save();
    }
}
