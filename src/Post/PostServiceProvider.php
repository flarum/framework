<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

        $this->registerPostTypes();

        $events = $this->app->make('events');
        $events->subscribe('Flarum\Post\PostPolicy');
    }

    public function registerPostTypes()
    {
        $models = [
            'Flarum\Post\CommentPost',
            'Flarum\Post\DiscussionRenamedPost'
        ];

        $this->app->make('events')->fire(
            new ConfigurePostTypes($models)
        );

        foreach ($models as $model) {
            Post::setModel($model::$type, $model);
        }
    }
}
