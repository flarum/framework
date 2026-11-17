<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Discussion\Discussion;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\Post;

class LikeServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        $captured = new \WeakMap;
        $events = $this->container->make(\Illuminate\Contracts\Events\Dispatcher::class);
        // Post events use the concrete subclass (CommentPost, etc.), so a
        // Post::deleting listener alone would miss ordinary comments.
        $events->listen('eloquent.deleting: *', function ($event, array $models) use ($captured) {
            $model = $models[0];
            if ($model instanceof Post) {
                $captured[$model] = $this->container->make(LikeCount::class)->capture(Post::query()->whereKey($model->id));
            } elseif ($model instanceof Discussion) {
                $captured[$model] = $this->container->make(LikeCount::class)->capture(Post::query()->where('discussion_id', $model->id));
            }
        });
        $events->listen('eloquent.deleted: *', function ($event, array $models) use ($captured) {
            $model = $models[0];
            if (isset($captured[$model])) {
                $this->container->make(LikeCount::class)->removed($model->getConnection(), $captured[$model]);
                unset($captured[$model]);
            }
        });
        $events->listen('eloquent.updated: *', function ($event, array $models) {
            $post = $models[0];
            if ($post instanceof Post && $post->wasChanged('is_private')) {
                $this->container->make(LikeCount::class)->sync($post);
            }
        });
    }
}
