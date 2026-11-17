<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Illuminate\Contracts\Events\Dispatcher;

class LikeServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        $affected = new \WeakMap;
        $counts = $this->container->make(LikeCount::class);

        CommentPost::deleting(function (CommentPost $post) use ($affected, $counts) {
            $affected[$post] = $post->type === 'comment' && ! $post->is_private
                ? $counts->affectedUserIds(Post::query()->whereKey($post->id))
                : [];
        });
        CommentPost::deleted(function (CommentPost $post) use ($affected, $counts) {
            $counts->decrement($affected[$post] ?? []);
            unset($affected[$post]);
        });
        Discussion::deleting(function (Discussion $discussion) use ($affected, $counts) {
            $affected[$discussion] = $counts->affectedUserIds(Post::query()->where('discussion_id', $discussion->id));
        });
        $this->container->make(Dispatcher::class)->listen(DiscussionDeleted::class, function (DiscussionDeleted $event) use ($affected, $counts) {
            // The domain event is dispatched after the discussion's posts
            // have been removed, including on SQLite without FK cascades.
            $counts->refresh($affected[$event->discussion] ?? []);
            unset($affected[$event->discussion]);
        });
        CommentPost::updated(function (CommentPost $post) use ($counts) {
            if ($post->wasChanged('is_private')) {
                $counts->refresh($counts->affectedUserIds(Post::query()->whereKey($post->id)));
            }
        });
    }
}
