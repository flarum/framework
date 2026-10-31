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
    /**
     * @var array<int, int[]> Liker ids of posts being deleted, by post object.
     */
    protected array $likers = [];

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PostWasLiked::class, $this->whenPostWasLiked(...));
        $events->listen(PostWasUnliked::class, $this->whenPostWasUnliked(...));
        $events->listen(Deleting::class, $this->whenPostIsBeingDeleted(...));
        $events->listen(Deleted::class, $this->whenPostWasDeleted(...));
    }

    public function whenPostWasLiked(PostWasLiked $event): void
    {
        $this->refresh([$event->user->id]);
    }

    public function whenPostWasUnliked(PostWasUnliked $event): void
    {
        $this->refresh([$event->user->id]);
    }

    public function whenPostIsBeingDeleted(Deleting $event): void
    {
        // The likes go with the post (post_likes cascades on delete), so note
        // who liked it while the rows are still there. Only the ids are kept,
        // as a post can have many thousands of likers.
        $this->likers[spl_object_id($event->post)] = $event->post->likes()->pluck('users.id')->all();
    }

    public function whenPostWasDeleted(Deleted $event): void
    {
        $key = spl_object_id($event->post);
        $likers = $this->likers[$key] ?? [];

        unset($this->likers[$key]);

        $this->refresh($likers);
    }

    /**
     * Recount the given users in one UPDATE per batch, without loading or
     * saving models. Nothing listens for like_count changes.
     *
     * @param int[] $userIds
     */
    protected function refresh(array $userIds): void
    {
        $likeCount = Post::query()->getConnection()->table('post_likes')
            ->join('posts', 'posts.id', '=', 'post_likes.post_id')
            ->whereColumn('post_likes.user_id', 'users.id')
            ->where('posts.is_private', false)
            ->selectRaw('COUNT(*)');

        foreach (array_chunk(array_values(array_unique($userIds)), 1000) as $chunk) {
            User::query()->getConnection()->table('users')
                ->whereIn('id', $chunk)
                ->update(['like_count' => $likeCount]);
        }
    }
}
