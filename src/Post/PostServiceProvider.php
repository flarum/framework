<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Flarum\Event\ConfigurePostTypes;
use Flarum\Foundation\AbstractServiceProvider;

class PostServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        CommentPost::setFormatter($this->app->make('flarum.formatter'));

        $this->setPostTypes();

        $events = $this->app->make('events');
        $events->subscribe(PostPolicy::class);
    }

    protected function setPostTypes()
    {
        $models = [
            CommentPost::class,
            DiscussionRenamedPost::class
        ];

        // Deprecated in beta 15, remove in beta 16.
        $this->app->make('events')->dispatch(
            new ConfigurePostTypes($models)
        );

        foreach ($models as $model) {
            Post::setModel($model::$type, $model);
        }
    }
}
