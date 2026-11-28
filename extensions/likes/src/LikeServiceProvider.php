<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\DeletingPosts;
use Illuminate\Contracts\Events\Dispatcher;

class LikeServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        $counts = $this->container->make(LikeCount::class);

        $this->container->make(Dispatcher::class)->listen(DeletingPosts::class, $counts->prepareDeletion(...));

        CommentPost::updated(function (CommentPost $post) use ($counts) {
            // A post counts only while it is a visible comment. The saving
            // locks keep the pivot stable, so the change is one per liker.
            // `getOriginal()` is the row as it was under that lock, not as the
            // request loaded it (Post::syncOriginalFromLock()), so two
            // approvals of the same post cannot both count it as private.
            $wasCounted = $post->getOriginal('type') === 'comment' && ! $post->getOriginal('is_private');
            $counted = $post->type === 'comment' && ! $post->is_private;

            if ($wasCounted !== $counted) {
                $counts->adjustPostLikers((int) $post->id, $counted ? 1 : -1);
            }
        });
    }
}
