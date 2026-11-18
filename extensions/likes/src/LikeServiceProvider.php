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
            if ($post->wasChanged('is_private')) {
                $ids = $post->getConnection()->table('post_likes')->where('post_id', $post->id)
                    ->lockForUpdate()->pluck('user_id')->all();
                $counts->refresh($ids);
            }
        });
    }
}
